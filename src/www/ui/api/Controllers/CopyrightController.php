<?php
/*
 SPDX-FileCopyrightText: © 2018 Siemens AG
 Author: Gaurav Mishra <mishra.gaurav@siemens.com>
 SPDX-FileCopyrightText: © 2023 Soham Banerjee <sohambanerjee4abc@hotmail.com>
 SPDX-License-Identifier: GPL-2.0-only
*/

/**
 * @file
 * @brief Controller for copyright queries
 */

namespace Fossology\UI\Api\Controllers;

use Fossology\Lib\Auth\Auth;
use Fossology\Lib\Dao\UploadDao;
use Fossology\Lib\Db\DbManager;
use Fossology\UI\Api\Helper\ResponseHelper;
use Fossology\UI\Api\Models\Info;
use Fossology\UI\Api\Models\InfoType;
use Psr\Http\Message\ServerRequestInterface;
use Fossology\Lib\Data\Tree\ItemTreeBounds;


class CopyrightController extends RestController
{
  /**
   * @var ContainerInterface $container
   * Slim container
   */
  protected $container;

  /**
   * @var ClearingDao
   */
  private $clearingDao;

  /**
   * @var LicenseDao $licenseDao
   * License Dao object
   */
  private $licenseDao;

  /**
   * @var UploadDao $uploadDao
   * Upload Dao object
   */
  private $uploadDao;

  /**
   * @var CopyrightHist $CopyrightHist
   * Copyright Histogram object
   */
  private $CopyrightHist;

  /**
   * @var CopyrightDao $copyrightDao
   * Copyright Dao object
   */
  private $copyrightDao;

  public function __construct($container)
  {
    parent::__construct($container);
    $this->container = $container;
    $this->clearingDao = $this->container->get('dao.clearing');
    $this->licenseDao = $this->container->get('dao.license');
    $this->uploadDao = $container->get('dao.upload');
    $this->restHelper = $container->get('helper.restHelper');
    $this->copyrightDao = $container->get('dao.copyright');
    $this->CopyrightHist = $this->restHelper->getPlugin('ajax-copyright-hist');
  }

  /**
   * Update copyrights for a particular file
   *
   * @param  ServerRequestInterface $request
   * @param  ResponseHelper         $response
   * @param  array                  $args
   * @return ResponseHelper
   */

  public function UpdateFileCopyrights($request, $response, $args)
  {
    try {
      $uploadDao = $this->restHelper->getUploadDao();
      $uploadTreeId = intval($args['itemId']);
      $copyrightHash = ($args['hash']);
      $userId = $this->restHelper->getUserId();
      $UploadTreeTableName = $uploadDao->getUploadtreeTableName($uploadTreeId);
      $cpTable = $this->CopyrightHist->getTableName('statement');
      $returnVal = null;
      $item = $uploadDao->getItemTreeBounds($uploadTreeId, $UploadTreeTableName);
      $body = $this->getParsedBody($request);
      $content = $body['content'];

      if (!$this->dbHelper->doesIdExist($uploadDao->getUploadtreeTableName($uploadTreeId), "uploadtree_pk", $uploadTreeId)) {
        $returnVal = new Info(404, "Item does not exist", InfoType::ERROR);
      }
      if ($returnVal !== null) {
        return $response->withJson($returnVal->getArray(), $returnVal->getCode());
      }
      $this->copyrightDao->updateTable($item, $copyrightHash, $content, $userId, $cpTable);
      $returnVal = new Info(200, "Successfully Updated Copyright.", InfoType::INFO);
      return $response->withJson($returnVal->getArray(), 200);
    } catch (\Exception $e) {
      $returnVal = new Info(500, $e->getMessage(), InfoType::ERROR);
      return $response->withJson($returnVal->getArray(), $returnVal->getCode());
    }
  }
}

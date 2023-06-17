<?php
/*
SPDX-FileCopyrightText: © 2023 Soham Banerjee <sohambanerjee4abc@hotmail.com>
  SPDX-License-Identifier: GPL-2.0-only
  */

/**
 * @file
 * @brief Tests for CopyrightController
 */

namespace Fossology\UI\Api\Test\Controllers;

use CopyrightHistogramProcessPost;
use Fossology\Lib\Auth\Auth;
use Fossology\Lib\Dao\ClearingDao;
use Fossology\Lib\Dao\CopyrightDao;
use Fossology\Lib\Dao\LicenseDao;
use Fossology\Lib\Dao\UploadDao;
use Fossology\Lib\Data\DecisionTypes;
use Fossology\Lib\Data\Tree\ItemTreeBounds;
use Fossology\Lib\Data\UploadStatus;
use Fossology\Lib\Db\DbManager;
use Fossology\UI\Api\Controllers\CopyrightController;
use Fossology\UI\Api\Helper\DbHelper;
use Fossology\UI\Api\Helper\ResponseHelper;
use Fossology\UI\Api\Helper\RestHelper;
use Fossology\UI\Api\Models\Info;
use Fossology\UI\Api\Models\InfoType;
use Fossology\UI\Api\Models\License;
use Slim\Psr7\Factory\StreamFactory;
use Mockery as M;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Uri;

/**
 * @class UploadControllerTest
 * @brief Unit tests for UploadController
 */
class CopyrightControllerTest extends \PHPUnit\Framework\TestCase
{
  /**
   * @var DbHelper $dbHelper
   * DbHelper mock
   */
  private $dbHelper;

  /**
   * @var DbManager $dbManager
   * Dbmanager mock
   */
  private $dbManager;

  /**
   * @var RestHelper $restHelper
   * RestHelper mock
   */
  private $restHelper;

  /**
   * @var CopyrightController $CopyrightController
   * CopyrightController mock
   */
  private $CopyrightController;

  /**
   * @var UploadDao $uploadDao
   * UploadDao mock
   */
  private $uploadDao;

  /**
   * @var CopyrightHistogramProcessPost $CopyrightHistPlugin
   * CopyrightHistogramProcessPost mock
   */
  private $CopyrightHistPlugin;

  /**
   * @var LicenseDao $licenseDao
   * LicenseDao mock
   */
  private $licenseDao;

  /**
   * @var ClearingDao $clearingDao
   * ClearingDao mock
   */
  private $clearingDao;

  /**
   * @var CopyrightDao $copyrightDao
   * CopyrightDao mock
   */
  private $copyrightDao;

  /**
   * @var StreamFactory $streamFactory
   * Stream factory to create body streams.
   */
  private $streamFactory;

  /**
   * @var DecisionTypes $decisionTypes
   * Decision types object
   */
  private $decisionTypes;

  /**
   * @var ItemTreeBounds $decisionTypes
   * Decision types object
   */
  private $itemTreeBounds;

  /**
   * @var AssertCountBefore $assertCountBefore
   * Assert count object
   */
  private $assertCountBefore;

  /**
   * @brief Setup test objects
   * @see PHPUnit_Framework_TestCase::setUp()
   */
  protected function setUp(): void
  {
    global $container;
    $this->userId = 2;
    $this->groupId = 2;
    $container = M::mock('ContainerBuilder');
    $this->dbHelper = M::mock(DbHelper::class);
    $this->dbManager = M::mock(DbManager::class);
    $this->restHelper = M::mock(RestHelper::class);
    $this->uploadDao = M::mock(UploadDao::class);
    $this->clearingDao = M::mock(ClearingDao::class);
    $this->CopyrightHistPlugin = M::mock(CopyrightHistogramProcessPost::class);
    $this->decisionTypes = M::mock(DecisionTypes::class);
    $this->licenseDao = M::mock(LicenseDao::class);
    $this->copyrightDao = M::mock(CopyrightDao::class);
    $this->itemTreeBounds = M::mock(ItemTreeBounds::class);

    $container->shouldReceive('get')->withArgs(array(
      'helper.restHelper'
    ))->andReturn($this->restHelper);

    $this->restHelper->shouldReceive('getPlugin')
      ->withArgs(array('ajax-copyright-hist'))->andReturn($this->CopyrightHistPlugin);

    $this->dbManager->shouldReceive('getSingleRow')
      ->withArgs([M::any(), [
        $this->groupId, UploadStatus::OPEN,
        Auth::PERM_READ
      ]]);
    $this->dbHelper->shouldReceive('getDbManager')->andReturn($this->dbManager);

    $this->restHelper->shouldReceive('getDbHelper')->andReturn($this->dbHelper);
    $this->restHelper->shouldReceive('getGroupId')->andReturn($this->groupId);
    $this->restHelper->shouldReceive('getUserId')->andReturn($this->userId);
    $this->restHelper->shouldReceive('getUploadDao')->andReturn($this->uploadDao);
    $container->shouldReceive('get')->withArgs(['decision.types'])->andReturn($this->decisionTypes);
    $container->shouldReceive('get')->withArgs(['dao.license'])->andReturn($this->licenseDao);
    $container->shouldReceive('get')->withArgs(['dao.clearing'])->andReturn($this->clearingDao);
    $container->shouldReceive('get')->withArgs(['dao.upload'])->andReturn($this->uploadDao);
    $container->shouldReceive('get')->withArgs(['dao.copyright'])->andReturn($this->copyrightDao);
    $this->CopyrightController = new CopyrightController($container);
    $this->assertCountBefore = \Hamcrest\MatcherAssert::getCount();
    $this->streamFactory = new StreamFactory();
  }

  /**
   * Helper function to get JSON array from response
   *
   * @param Response $response
   * @return array Decoded response
   */
  private function getResponseJson($response)
  {
    $response->getBody()->seek(0);
    return json_decode($response->getBody()->getContents(), true);
  }

  /**
   * Helper function to generate uploads bounds
   * @param integer $id Upload id (if > 4, return false)
   * @return false|ItemTreeBounds
   */

  /**
   * @test
   * -# Test for CopyrightController::getFileCopyrights()
   * -# Check if response status is 200 and RES body matches
   */
  public function testUpdateFileCopyrights()
  {
    $uploadId = 4;
    $itemId = 98;
    $userId = 2;
    $cpTable = 'copyright';
    $lft = 112;
    $rgt = 113;
    $copyrightHash = 'a35595408e32c9c7cd405b2a0530a39e';
    $uploadTreeTableName = "uploadtree_a";
    $text = "text";

    $item = new ItemTreeBounds($itemId, $uploadTreeTableName, $uploadId, $lft, $rgt);
    $rq = [
      "content" => $text,
    ];
    $this->dbHelper->shouldReceive('doesIdExist')
      ->withArgs(["upload", "upload_pk", $itemId])->andReturn(true);


    $this->dbHelper->shouldReceive('doesIdExist')
      ->withArgs(["uploadtree_a", "uploadtree_pk", $itemId])->andReturn(true);
    $this->dbHelper->shouldReceive('doesIdExist')
      ->withArgs(["copyright", "hash", $copyrightHash])->andReturn(true);
    $reqBody = $this->streamFactory->createStream(json_encode(
      $rq
    ));
    $requestHeaders = new Headers();
    $requestHeaders->setHeader('Content-Type', 'application/json');
    $request = new Request(
      "PUT",
      new Uri("HTTP", "localhost"),
      $requestHeaders,
      [],
      [],
      $reqBody
    );
    $this->CopyrightHistPlugin->shouldReceive('getTableName')->withArgs(['statement'])->andReturn($cpTable);
    $this->uploadDao->shouldReceive('getUploadtreeTableName')->withArgs([98])->andReturn($uploadTreeTableName);
    $this->uploadDao->shouldReceive('getItemTreeBounds')->withArgs([$itemId, $uploadTreeTableName])->andReturnSelf();
    $this->copyrightDao->shouldReceive('updateTable')->withArgs([$item, $copyrightHash, $text, $userId, $cpTable])->andReturn(null);
    $info = new Info(200, "Successfully removed Copyright.", InfoType::INFO);
    $expectedResponse = (new ResponseHelper())->withJson($info->getArray(), $info->getCode());
    $actualResponse = $this->CopyrightController->UpdateFileCopyrights($request, new ResponseHelper(), ['id' => $uploadId, 'itemId' => $itemId, 'hash' => $copyrightHash]);
    //$this->assertEquals($expectedResponse->getStatusCode(), $actualResponse->getStatusCode());
    $this->assertEquals($this->getResponseJson($actualResponse), $this->getResponseJson($expectedResponse));
  }
}

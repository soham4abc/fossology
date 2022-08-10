/*
 SPDX-FileCopyrightText: © 2012 Hewlett-Packard Development Company, L.P.

 SPDX-License-Identifier: GPL-2.0-only
*/

/* fossology includes */
#include <libfossology.h>

/// the number of minutes to wait before closing
#define MINUTES_TO_WAIT 1

/**
 * @file
 * @date June 1, 2012
 * @brief This is a simple test agent meant to be used by Unit and functional
 *        tests to confirm a correctly working scheduler.
 *
 *        This particular agent
 *        will startup and then do nothing for at least MINUTES_TO_WAIT minutes.
 *        This tests to make sure that the scheduler will not kill an agent that
 *        has NOKILL set to true.
 *
 * @note This is a working agent
 */

int main(int argc, char** argv)
{
  int i;

  fo_scheduler_connect(&argc, argv, NULL);
  fo_scheduler_set_special(SPECIAL_NOKILL, 1);

  for(i = 0; i < MINUTES_TO_WAIT; i++)
    sleep(60);

  fo_scheduler_next();
  fo_scheduler_disconnect(0);

  return 0;
}



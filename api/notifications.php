<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package mod_studio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

/*
 * This function returns the social data count for the requested list
 * of slot ids.  The counts are in the context of the given user id.
 *
 * @param int $userid The user to get social data for.
 * @param array $slots List of slot ids to get social data from.
 * @return array Return array of social data for list of slots.
 */
function studio_api_notifications_get_activities($userid, $slots) {

    // If there are no slots provided, exit now.
    if (empty($slots)) {
        return false;
    }

    global $DB;

    $sql = <<<EOF
SELECT sf.contentid AS contentid, sf.setid,
       (        SELECT count(sc1.id)
                  FROM {openstudio_comments} sc1
                 WHERE sc1.contentid = sf.contentid
                   AND sc1.deletedtime IS NULL
                   AND sc1.userid != ?
        AND NOT EXISTS (SELECT 1
                          FROM {openstudio_flags} sc1f
                         WHERE sc1f.contentid = sc1.contentid
                           AND sc1f.flagid = 6
                           AND sc1f.userid = ?)) AS commentsnewslot,
       (    SELECT count(sc2.id)
              FROM {openstudio_comments} sc2
             WHERE sc2.contentid = sf.contentid
               AND sc2.deletedtime IS NULL
               AND sc2.userid != ?
        AND EXISTS (SELECT 1
                      FROM {openstudio_flags} sc2f
                     WHERE sc2f.contentid = sc2.contentid
                       AND sc2f.flagid = 6
                       AND sc2f.userid = ?
                       AND sc2.timemodified > sc2f.timemodified)) AS commentsnew,
       (    SELECT count(sc3.id)
              FROM {openstudio_comments} sc3
             WHERE sc3.contentid = sf.contentid
               AND sc3.deletedtime IS NULL
        AND (EXISTS (SELECT 1
                      FROM {openstudio_flags} sc3f
                     WHERE sc3f.contentid = sc3.contentid
                       AND sc3f.flagid = 6
                       AND sc3f.userid = ?
                       AND sc3.timemodified <= sc3f.timemodified) OR sc3.userid = ?)) AS commentsold,
       (         SELECT count(sf5_1.id)
                   FROM {openstudio_flags} sf5_1
                  WHERE sf5_1.contentid = sf.contentid
                    AND sf5_1.flagid = 5
                    AND sf5_1.userid != ?
        AND NOT EXISTS (SELECT 1
                          FROM {openstudio_flags} sf5_1f
                         WHERE sf5_1f.contentid = sf5_1.contentid
                           AND sf5_1f.flagid = 6
                           AND sf5_1f.userid = ?)) AS inspirednewslot,
       (    SELECT count(sf5_2.id)
              FROM {openstudio_flags} sf5_2
             WHERE sf5_2.contentid = sf.contentid
               AND sf5_2.flagid = 5
               AND sf5_2.userid != ?
        AND EXISTS (SELECT 1
                      FROM {openstudio_flags} sf5_2f
                     WHERE sf5_2f.contentid = sf5_2.contentid
                       AND sf5_2f.flagid = 6
                       AND sf5_2f.userid = ?
                       AND sf5_2.timemodified > sf5_2f.timemodified)) AS inspirednew,
       (    SELECT count(sf5_3.id)
              FROM {openstudio_flags} sf5_3
             WHERE sf5_3.contentid = sf.contentid
               AND sf5_3.flagid = 5
        AND (EXISTS (SELECT 1
                      FROM {openstudio_flags} sf5_3f
                     WHERE sf5_3f.contentid = sf5_3.contentid
                       AND sf5_3f.flagid = 6
                       AND sf5_3f.userid = ?
                       AND sf5_3.timemodified <= sf5_3f.timemodified) OR sf5_3.userid = ?)) AS inspiredold,
       (         SELECT count(sf5_1.id)
                   FROM {openstudio_flags} sf5_1
                  WHERE sf5_1.contentid = sf.contentid
                    AND sf5_1.flagid = 4
                    AND sf5_1.userid != ?
        AND NOT EXISTS (SELECT 1
                          FROM {openstudio_flags} sf5_1f
                         WHERE sf5_1f.contentid = sf5_1.contentid
                           AND sf5_1f.flagid = 6
                           AND sf5_1f.userid = ?)) AS mademelaughnewslot,
       (    SELECT count(sf5_2.id)
              FROM {openstudio_flags} sf5_2
             WHERE sf5_2.contentid = sf.contentid
               AND sf5_2.flagid = 4
               AND sf5_2.userid != ?
        AND EXISTS (SELECT 1
                      FROM {openstudio_flags} sf5_2f
                     WHERE sf5_2f.contentid = sf5_2.contentid
                       AND sf5_2f.flagid = 6
                       AND sf5_2f.userid = ?
                       AND sf5_2.timemodified > sf5_2f.timemodified)) AS mademelaughnew,
       (    SELECT count(sf5_3.id)
              FROM {openstudio_flags} sf5_3
             WHERE sf5_3.contentid = sf.contentid
               AND sf5_3.flagid = 4
        AND (EXISTS (SELECT 1
                      FROM {openstudio_flags} sf5_3f
                     WHERE sf5_3f.contentid = sf5_3.contentid
                       AND sf5_3f.flagid = 6
                       AND sf5_3f.userid = ?
                       AND sf5_3.timemodified <= sf5_3f.timemodified) OR sf5_3.userid = ?)) AS mademelaughold,
       (         SELECT count(sf5_1.id)
                   FROM {openstudio_flags} sf5_1
                  WHERE sf5_1.contentid = sf.contentid
                    AND sf5_1.flagid = 2
                    AND sf5_1.userid != ?
        AND NOT EXISTS (SELECT 1
                          FROM {openstudio_flags} sf5_1f
                         WHERE sf5_1f.contentid = sf5_1.contentid
                           AND sf5_1f.flagid = 6
                           AND sf5_1f.userid = ?)) AS favouritenewslot,
       (    SELECT count(sf5_2.id)
              FROM {openstudio_flags} sf5_2
             WHERE sf5_2.contentid = sf.contentid
               AND sf5_2.flagid = 2
               AND sf5_2.userid != ?
        AND EXISTS (SELECT 1
                      FROM {openstudio_flags} sf5_2f
                     WHERE sf5_2f.contentid = sf5_2.contentid
                       AND sf5_2f.flagid = 6
                       AND sf5_2f.userid = ?
                       AND sf5_2.timemodified > sf5_2f.timemodified)) AS favouritenew,
       (    SELECT count(sf5_3.id)
              FROM {openstudio_flags} sf5_3
             WHERE sf5_3.contentid = sf.contentid
               AND sf5_3.flagid = 2
        AND (EXISTS (SELECT 1
                      FROM {openstudio_flags} sf5_3f
                     WHERE sf5_3f.contentid = sf5_3.contentid
                       AND sf5_3f.flagid = 6
                       AND sf5_3f.userid = ?
                       AND sf5_3.timemodified <= sf5_3f.timemodified) OR sf5_3.userid = ?)) AS favouriteold
  FROM {openstudio_flags} sf

EOF;

    $sqlparams = array();
    for ($counter = 0; $counter < 24; $counter++) {
        $sqlparams[] = $userid;
    }

    if (!empty($slots)) {
        list($filterslotdatasql, $filterslotdataparams) = $DB->get_in_or_equal($slots);
        $sqlparams = array_merge($sqlparams, $filterslotdataparams);
        $sqlparams = array_merge($sqlparams, $filterslotdataparams);
        $sql .= " WHERE (sf.contentid {$filterslotdatasql}) OR (sf.setid {$filterslotdatasql}) ";
    }
    $sql .= " GROUP BY sf.contentid, sf.setid ";

    $results = $DB->get_recordset_sql($sql, $sqlparams);
    if (!$results->valid()) {
        return false;
    }

    $slotdata = array();
    $setdata = array();
    foreach ($results as $slot) {
        $slotdata[$slot->slotid] = $slot;
        $slotdata[$slot->slotid]->set = false;

        if ($slot->setid > 0) {
            if (array_key_exists($slot->setid, $setdata)) {
                $setdata[$slot->setid]->slots += 1;
                $setdata[$slot->setid]->commentsnewslot += $slot->commentsnewslot;
                $setdata[$slot->setid]->commentsnew += $slot->commentsnew;
                $setdata[$slot->setid]->commentsold += $slot->commentsold;
                $setdata[$slot->setid]->inspirednewslot += $slot->inspirednewslot;
                $setdata[$slot->setid]->inspirednew += $slot->inspirednew;
                $setdata[$slot->setid]->inspiredold += $slot->inspiredold;
                $setdata[$slot->setid]->mademelaughnewslot += $slot->mademelaughnewslot;
                $setdata[$slot->setid]->mademelaughnew += $slot->mademelaughnew;
                $setdata[$slot->setid]->mademelaughold += $slot->mademelaughold;
                $setdata[$slot->setid]->favouritenewslot += $slot->favouritenewslot;
                $setdata[$slot->setid]->favouritenew += $slot->favouritenew;
                $setdata[$slot->setid]->favouriteold += $slot->favouriteold;
            } else {
                $setdata[$slot->setid] = (object) array();
                $setdata[$slot->setid]->slotid = $slot->slotid;
                $setdata[$slot->setid]->setid = $slot->setid;
                $setdata[$slot->setid]->slots = 1;
                $setdata[$slot->setid]->commentsnewslot = $slot->commentsnewslot;
                $setdata[$slot->setid]->commentsnew = $slot->commentsnew;
                $setdata[$slot->setid]->commentsold = $slot->commentsold;
                $setdata[$slot->setid]->inspirednewslot = $slot->inspirednewslot;
                $setdata[$slot->setid]->inspirednew = $slot->inspirednew;
                $setdata[$slot->setid]->inspiredold = $slot->inspiredold;
                $setdata[$slot->setid]->mademelaughnewslot = $slot->mademelaughnewslot;
                $setdata[$slot->setid]->mademelaughnew = $slot->mademelaughnew;
                $setdata[$slot->setid]->mademelaughold = $slot->mademelaughold;
                $setdata[$slot->setid]->favouritenewslot = $slot->favouritenewslot;
                $setdata[$slot->setid]->favouritenew = $slot->favouritenew;
                $setdata[$slot->setid]->favouriteold = $slot->favouriteold;
            }
        }
    }

    foreach ($setdata as $setdataid => $setdataitem) {
        if (array_key_exists($setdataid, $slotdata)) {
            $slotdata[$setdataid]->set = $setdataitem;
        }
    }

    return $slotdata;
}
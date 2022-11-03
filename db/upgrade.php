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
 * @package mod_openstudio
 * @copyright The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_openstudio_upgrade($oldversion=0) {
    global $DB;

    $result = true;
    $dbman = $DB->get_manager();

    if ($oldversion < 2016102501) {

        // Studio savepoint reached.
        upgrade_mod_savepoint(true, 2016102501, 'openstudio');
    }

    if ($oldversion < 2017040401) {

        // Define table openstudio_notifications to be created.
        $table = new xmldb_table('openstudio_notifications');

        // Adding fields to table openstudio_notifications.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('commentid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('flagid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('message', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('url', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userfrom', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('icon', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeread', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table openstudio_notifications.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('contentid', XMLDB_KEY_FOREIGN, array('contentid'), 'openstudio_contents', array('id'));
        $table->add_key('commentid', XMLDB_KEY_FOREIGN, array('commentid'), 'openstudio_comments', array('id'));
        $table->add_key('userfrom', XMLDB_KEY_FOREIGN, array('userfrom'), 'user', array('id'));

        // Adding indexes to table openstudio_notifications.
        $table->add_index('flagid', XMLDB_INDEX_NOTUNIQUE, array('flagid'));

        // Conditionally launch create table for openstudio_notifications.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Openstudio savepoint reached.
        upgrade_mod_savepoint(true, 2017040401, 'openstudio');
    }

    if ($oldversion < 2017091000) {

        // Add timemodified field for applying global search to oublog activity.
        $table = new xmldb_table('openstudio');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            // Add the field but allowing nulls.
            $dbman->add_field($table, $field);
            // Set the field to 0 for everything.
            $DB->set_field('openstudio', 'timemodified', '0');
            // Changing nullability of field timemodified to not null.
            $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null,
                XMLDB_NOTNULL, null, null);
            // Launch change of nullability for field themetype.
            $dbman->change_field_notnull($table, $field);
        }

        // Openstudio savepoint reached.
        upgrade_mod_savepoint(true, 2017091000, 'openstudio');
    }

    if ($oldversion < 2018122500) {

        // Define field retainimagemetadata to be added to openstudio_contents.
        $table = new xmldb_table('openstudio_contents');
        $field = new xmldb_field('retainimagemetadata', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'lockprocessed');

        // Conditionally launch add field retainimagemetadata.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Openstudio savepoint reached.
        upgrade_mod_savepoint(true, 2018122500, 'openstudio');
    }

    if ($oldversion < 2019011600) {
        try {
            core_filetypes::add_type('ipynb', 'application/x-ipynb+json', 'text');
        } catch (coding_exception $e) {
            // To stop any error messages being displayed since if type is already added add_type throws an exception.
        }
    }

    if ($oldversion < 2019111300) {
        // Add latesubmissionmessage field setting.
        $table = new xmldb_table('openstudio');
        $field = new xmldb_field('latesubmissionmessage', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Conditionally launch add field latesubmissionmessage.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Openstudio savepoint reached.
        upgrade_mod_savepoint(true, 2019111300, 'openstudio');
    }

    if ($oldversion < 2022111400) {
        $DB->execute("UPDATE {openstudio_comments} as oc
                        SET deletedby = subquery.deletedby,
					        deletedtime = subquery.deletedtime
                       FROM (
						   SELECT id, deletedby, deletedtime
						     FROM {openstudio_comments}
						    WHERE inreplyto IS NULL AND deletedby IS NOT NULL
						   ) as subquery
						    WHERE oc.deletedby IS NULL 
						      AND oc.inreplyto = subquery.id");
        upgrade_mod_savepoint(true, 2022111400, 'openstudio');
    }
        // Must always return true from these functions.
    return $result;


}

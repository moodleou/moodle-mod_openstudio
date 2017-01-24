@ou @ou_vle @mod @mod_openstudio @mod_openstudio_test_honesty
Feature: Accept plagarism statement
    In order to confirm I am not going to plagarise other peoples' work
    As a student
    I need to confirm acceptance of a statement before using open studio.

    Background: Setup course and studio
        Given the following "users" exist:
            | username | firstname | lastname | email |
            | teacher1 | Teacher | 1 | teacher1@asd.com |
            | student1 | Student | 1 | student1@asd.com |
            | student2 | Student | 2 | student2@asd.com |
            | student3 | Student | 3 | student3@asd.com |
            | student4 | Student | 4 | student4@asd.com |
        And the following "courses" exist:
            | fullname | shortname | category |
            | Course 1 | C1 | 0 |
        And the following "course enrolments" exist:
            | user | course | role |
            | teacher1 | C1 | editingteacher |
            | student1 | C1 | student |
            | student2 | C1 | student |
        And the following "groups" exist:
            | name   | course | idnumber |
            | group1 | C1     | G1       |
        And the following "groupings" exist:
            | name      | course | idnumber |
            | grouping1 | C1     | GI1      |
        And the following "grouping groups" exist:
            | grouping | group |
            | GI1      | G1    |
        And the following "group members" exist:
            | user     | group  |
            | teacher1 | G1 |
            | student1 | G1 |
            | student2 | G1 |
        And I log in as "teacher1"
        And I am on site homepage
        And I follow "Course 1"
        And I turn editing mode on
        And I add a "Open Studio" to section "1" and I fill the form with:
            | Name                         | Test Open Studio name 1      |
            | Description                  | Test Open Studio description |
            | Group mode                   | Visible groups               |
            | Grouping                     | grouping1                    |
            | Enable pinboard              | 99                           |
            | Abuse reports are emailed to | teacher1@asd.com             |
            | ID number                    | OS1                          |
        And Open Studio test instance is configured for "Test Open Studio name 1"

    Scenario: Test acceptance of honesty check
        When I follow "Test Open Studio name 1"
        Then I should see "Plagiarism statement"

        # Choose cancel
        And I press "Cancel"
        Then I should see "Test Open Studio name 1"

        # Choose accept
        And I follow "Test Open Studio name 1"
        And I press "Accept"
        Then I should see "Add new content"

        # Switch studen1 user
        And I log out
        And I log in as "student1"
        And I follow "Course 1"
        And I follow "Test Open Studio name 1"
        Then I should see "Plagiarism statement"

        # Switch teacher1 user
        And I log out
        And I log in as "teacher1"
        And I am on site homepage
        And I follow "Course 1"
        And I follow "Test Open Studio name 1"
        Then I should see "Add new content"

@ou @ou_vle @mod @mod_openstudio @mod_openstudio_test_content @_file_upload @javascript
Feature: Create and edit contents
    When using Open Studio with other users
    As a teacher
    I need to navigate to content pages

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
            | student3 | C1 | student |
            | student4 | C1 | student |
        And the following "groups" exist:
            | name   | course | idnumber |
            | group1 | C1     | G1       |
            | group2 | C1     | G2       |
            | group3 | C1     | G3       |
        And the following "groupings" exist:
            | name      | course | idnumber |
            | grouping1 | C1     | GI1      |
        And the following "grouping groups" exist:
            | grouping | group |
            | GI1      | G1    |
            | GI1      | G2    |
            | GI1      | G3    |
        And the following "group members" exist:
            | user     | group  |
            | teacher1 | G1 |
            | student1 | G1 |
            | student2 | G1 |
            | teacher1 | G2 |
            | student2 | G2 |
            | student3 | G2 |
            | student3 | G3 |
        And I log in as "teacher1"
        And I am on site homepage
        And I follow "Course 1"
        And I turn editing mode on
        And I add a "OpenStudio 2 (pilot only)" to section "1" and I fill the form with:
            | Name                         | Test Open Studio name 1      |
            | Description                  | Test Open Studio description |
            | Group mode                   | Separate groups              |
            | Grouping                     | grouping1                    |
            | Enable pinboard              | 99                           |
            | Abuse reports are emailed to | teacher1@asd.com             |
            | ID number                    | OS1                          |
        And Open Studio test instance is configured for "Test Open Studio name 1"
        And all users have accepted the plagarism statement for "OS1" openstudio

    Scenario: Test Pagination without contents
        When I follow "Test Open Studio name 1"
        Then I should not see "Test content 12"
        Then I should not see "Next"

    Scenario: Test Pagination with contents
        When I log out
        And I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Open Studio name 1"
        And the following open studio "contents" exist:
            | openstudio | user     | name           | description                | visibility |
            | OS1        | student1 | Test content A | Test content 1 description | module     |
        And I wait "2" seconds
        And the following open studio "contents" exist:
            | openstudio | user     | name         | description                  | visibility |
            | OS1        | student1 | Test content | Test content 2 description   | module     |
            | OS1        | student1 | Test content | Test content 3 description   | module     |
            | OS1        | student1 | Test content | Test content 4 description   | module     |
            | OS1        | student1 | Test content | Test content 5 description   | module     |
            | OS1        | student1 | Test content | Test content 6 description   | module     |
            | OS1        | student1 | Test content | Test content 7 description   | module     |
            | OS1        | student1 | Test content | Test content 8 description   | module     |
            | OS1        | student1 | Test content | Test content 9 description   | module     |
            | OS1        | student1 | Test content | Test content 10 description  | module     |
            | OS1        | student1 | Test content | Test content 11 description  | module     |
            | OS1        | student1 | Test content | Test content 12 description  | module     |
            | OS1        | student1 | Test content | Test content 13 description  | module     |
            | OS1        | student1 | Test content | Test content 14 description  | module     |
            | OS1        | student1 | Test content | Test content 15 description  | module     |
            | OS1        | student1 | Test content | Test content 16 description  | module     |
            | OS1        | student1 | Test content | Test content 17 description  | module     |
            | OS1        | student1 | Test content | Test content 18 description  | module     |
            | OS1        | student1 | Test content | Test content 19 description  | module     |
            | OS1        | student1 | Test content | Test content 20 description  | module     |
            | OS1        | student1 | Test content | Test content 21 description  | module     |
            | OS1        | student1 | Test content | Test content 22 description  | module     |
            | OS1        | student1 | Test content | Test content 23 description  | module     |
            | OS1        | student1 | Test content | Test content 24 description  | module     |
            | OS1        | student1 | Test content | Test content 25 description  | module     |
            | OS1        | student1 | Test content | Test content 26 description  | module     |
            | OS1        | student1 | Test content | Test content 27 description  | module     |
            | OS1        | student1 | Test content | Test content 28 description  | module     |
            | OS1        | student1 | Test content | Test content 29 description  | module     |
            | OS1        | student1 | Test content | Test content 30 description  | module     |
            | OS1        | student1 | Test content | Test content 31 description  | module     |
            | OS1        | student1 | Test content | Test content 32 description  | module     |
            | OS1        | student1 | Test content | Test content 33 description  | module     |
            | OS1        | student1 | Test content | Test content 34 description  | module     |
            | OS1        | student1 | Test content | Test content 35 description  | module     |
            | OS1        | student1 | Test content | Test content 36 description  | module     |
            | OS1        | student1 | Test content | Test content 37 description  | module     |
            | OS1        | student1 | Test content | Test content 38 description  | module     |
            | OS1        | student1 | Test content | Test content 39 description  | module     |
            | OS1        | student1 | Test content | Test content 40 description  | module     |
            | OS1        | student1 | Test content | Test content 41 description  | module     |
            | OS1        | student1 | Test content | Test content 42 description  | module     |
            | OS1        | student1 | Test content | Test content 43 description  | module     |
            | OS1        | student1 | Test content | Test content 44 description  | module     |
            | OS1        | student1 | Test content | Test content 45 description  | module     |
            | OS1        | student1 | Test content | Test content 46 description  | module     |
            | OS1        | student1 | Test content | Test content 47 description  | module     |
            | OS1        | student1 | Test content | Test content 48 description  | module     |
            | OS1        | student1 | Test content | Test content 49 description  | module     |
            | OS1        | student1 | Test content | Test content 50 description  | module     |
            | OS1        | student1 | Test content | Test content 51 description  | module     |
        And I wait "2" seconds
        And the following open studio "contents" exist:
            | openstudio | user     | name            | description              | visibility |
            | OS1        | student1 | Test content N  | Test content 13 description | module     |

        When I reload the page
        And I follow "Shared content > My Module" in the openstudio navigation
        Then I should see "Test content N"
        And I should see "2"
        And I click on "select#filter_pagesize" "css_element"
        And I click on "option[value='50']" "css_element"
        When I click on ".openstudio-desktop-paging .next" "css_element"
        Then I should see "Test content A"
        And I should not see "Test content N"
        And I should see "1"
        And I click on ".openstudio-desktop-paging .previous" "css_element"
        Then I should see "Test content N"

        # Mobile view
        When I change viewport size to "320x768"
        And "Next" "link" should exist
        And I should not see "1" in the ".paging" "css_element"
        When I click on ".openstudio-mobile-paging-next" "css_element"
        Then I should see "Page 2" in the ".openstudio-mobile-paging-current" "css_element"
        Then I should see "Test content A"
        And I click on ".openstudio-mobile-paging-previous" "css_element"
        Then I should not see "Page 2"
        Then "Next" "link" should exist

@ou @ou_vle @mod @mod_openstudio @mod_openstudio_test_content @_file_upload
Feature: Create and edit contents
    When using Open Studio with other users
    As a teacher
    I need to create a content and upload a file

    Background: Setup course and studio
        Given the following "users" exist:
            | username | firstname | lastname | email            |
            | teacher1 | Teacher   | 1        | teacher1@asd.com |
            | student1 | Student   | 1        | student1@asd.com |
            | student2 | Student   | 2        | student2@asd.com |
            | student3 | Student   | 3        | student3@asd.com |
            | student4 | Student   | 4        | student4@asd.com |
        And the following "courses" exist:
            | fullname | shortname | category |
            | Course 1 | C1        | 0        |
        And the following "course enrolments" exist:
            | user | course | role           |
            | teacher1 | C1 | editingteacher |
            | student1 | C1 | student        |
            | student2 | C1 | student        |
            | student3 | C1 | student        |
            | student4 | C1 | student        |
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
            | teacher1 | G1     |
            | student1 | G1     |
            | student2 | G1     |
            | teacher1 | G2     |
            | student2 | G2     |
            | student3 | G2     |
            | teacher1 | G3     |
            | student3 | G3     |
            | student4 | G3     |
        And I log in as "teacher1"
        And I am on site homepage
        And I follow "Course 1"
        And I turn editing mode on
        And I add a "OpenStudio 2 (pilot only)" to section "1" and I fill the form with:
            | Name                         | Test Open Studio name 1      |
            | Description                  | Test Open Studio description |
            | Your word for 'My Module'    | Module 1                     |
            | Group mode                   | Visible groups               |
            | Grouping                     | grouping1                    |
            | Enable pinboard              | 99                           |
            | Abuse reports are emailed to | teacher1@asd.com             |
            | ID number                    | OS1                          |
        And Open Studio test instance is configured for "Test Open Studio name 1"
        And all users have accepted the plagarism statement for "OS1" openstudio

    @javascript
    Scenario: Test Open Studio My Pinboard View
        # Add new content view Only me
        When I follow "Test Open Studio name 1"
        Then I should see "Test Open Studio name 1"
        And I follow "Add new content"
        And I press "Add file"
        And I set the following fields to these values:
          | Who can view this content | Only me                                    |
          | Title                     | Test My Pinboard View                      |
          | Description               | My Pinboard View Description               |
          | Upload content            | mod/openstudio/tests/importfiles/test1.jpg |
        And I press "Save"
        And I follow "My Content > My Pinboard" in the openstudio navigation
        Then I should see "Test My Pinboard View"
        And I follow "Shared content > My Module" in the openstudio navigation
        Then I should not see "Test My Pinboard View"

        # Other user can't see content in My Pinbord
        And I am on site homepage
        And I log out
        And I log in as "student1"
        And I follow "Course 1"
        And I follow "Test Open Studio name 1"
        And I follow "My Content > My Pinboard" in the openstudio navigation
        Then I should not see "Test My Pinboard View"

        # Add Module view
        And I follow "Add new content"
        And I press "Add file"
        And I set the following fields to these values:
          | Who can view this content | My module                                       |
          | Title                     | My Module Title                                 |
          | Description               | My Module Description                           |
          | Upload content            | mod/openstudio/tests/importfiles/Winterfell.jpg |
        And I press "Save"
        And I follow "Shared content > My Module" in the openstudio navigation

        Then I should see "My Module Title"
        And I follow "My Content > My Pinboard" in the openstudio navigation
        Then I should see "My Module Title"

        # Other user can see content in My Module
        And I am on site homepage
        And I log out
        And I log in as "student2"
        And I follow "Course 1"
        And I follow "Test Open Studio name 1"
        And I follow "My Content" in the openstudio navigation
        
        And I follow "Shared content > My Module" in the openstudio navigation
        Then I should see "My Module Title"

@ou @ou_vle @mod @mod_openstudio @mod_openstudio_test_content @_file_upload @javascript
Feature: Create and edit contents
    When using Open Studio with other users
    As a teacher
    I need to create a content and upload a file

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
            | teacher1 | G3 |
            | student3 | G3 |
            | student4 | G3 |
        And I log in as "teacher1"

    Scenario: Edit content without upload file
        Given I am on site homepage
        And I follow "Course 1"
        And I turn editing mode on
        And I add a "Open Studio" to section "1" and I fill the form with:
          | Name                         | Test Open Studio name 1      |
          | Description                  | Test Open Studio description |
          | Abuse reports are emailed to | teacher1@asd.com             |
          | ID number                    | OS1                          |
        And all users have accepted the plagarism statement for "OS1" openstudio
        And I follow "Test Open Studio name 1"
        And I click on "div.openstudio-upload-container" "css_element"
        And I press "Add file"
        And I set the following fields to these values:
          | Who can view this content | My module                         |
          | Title                     | Test My Group Board View 1        |
          | Description               | My Group Board View Description 1 |
        And I press "Save"
        And I go to content edit view
        Then I should see "Test Open Studio name 1"
        And I should not see "Upload content"

        And I press "Add file"
        And I set the following fields to these values:
          | Who can view this content | My module                                  |
          | Title                     | Test My Group Board View modify 1          |
          | Description               | My Group Board View Description modify 1   |
          | Upload content            | mod/openstudio/tests/importfiles/test1.jpg |
        And I press "Save"
        And I follow "Shared content" in the openstudio navigation
        Then I should see "Test My Group Board View modify 1"
        And the "src" attribute of "div.openstudio-grid-item-content-preview img" "css_element" should contain "test1.jpg"
        And I should not see "Test My Group Board View 1"

    Scenario: Edit content with upload file
        Given I am on site homepage
        And I follow "Course 1"
        And I turn editing mode on
        And I add a "Open Studio" to section "1" and I fill the form with:
          | Name                         | Test Open Studio name 1      |
          | Description                  | Test Open Studio description |
          | Abuse reports are emailed to | teacher1@asd.com             |
          | ID number                    | OS1                          |
        And all users have accepted the plagarism statement for "OS1" openstudio
        And I follow "Test Open Studio name 1"
        And I click on "div.openstudio-upload-container" "css_element"
        And I press "Add file"
        And I set the following fields to these values:
          | Who can view this content | My module                                  |
          | Title                     | Test My Group Board View 1                 |
          | Description               | My Group Board View Description 1          |
          | Upload content            | mod/openstudio/tests/importfiles/test1.jpg |
        And I press "Save"
        And I go to content edit view
        Then I should see "Test Open Studio name 1"
        And I should see "Upload content"
        And I should see "test1.jpg"
        And I set the following fields to these values:
          | Who can view this content | My module                                |
          | Title                     | Test My Group Board View modify 1        |
          | Description               | My Group Board View Description modify 1 |
        And I press "Save"
        And I follow "Shared content" in the openstudio navigation
        Then I should see "Test My Group Board View modify 1"
        And the "src" attribute of "div.openstudio-grid-item-content-preview img" "css_element" should contain "test1.jpg"
        And I should not see "Test My Group Board View 1"

    Scenario: Edit content without web link
        Given I am on site homepage
        And I follow "Course 1"
        And I turn editing mode on
        And I add a "Open Studio" to section "1" and I fill the form with:
          | Name                         | Test Open Studio name 1      |
          | Description                  | Test Open Studio description |
          | Abuse reports are emailed to | teacher1@asd.com             |
          | ID number                    | OS1                          |
        And all users have accepted the plagarism statement for "OS1" openstudio
        And I follow "Test Open Studio name 1"
        And I click on "div.openstudio-upload-container" "css_element"
        And I press "Add web/embed link"
        And I set the following fields to these values:
          | Who can view this content | My module                         |
          | Title                     | Test My Group Board View 1        |
          | Description               | My Group Board View Description 1 |
        And I press "Save"
        And I go to content edit view
        Then I should see "Test Open Studio name 1"
        And I should see "Add web/embed link"

        And I press "Add web/embed link"
        And I set the following fields to these values:
          | Who can view this content | My module                                   |
          | Title                     | Test My Group Board View modify 1           |
          | Description               | My Group Board View Description modify 1    |
          | Web link                  | https://www.youtube.com/watch?v=BGD6L-4yceY |
        And I press "Save"
        And I follow "Shared content" in the openstudio navigation
        Then the "src" attribute of "div.openstudio-grid-item-content-preview img" "css_element" should contain "Youtube-61px"
        And I should see "Test My Group Board View modify 1"
        And I should not see "Test My Group Board View 1"

    Scenario: Edit content with web link
        Given I am on site homepage
        And I follow "Course 1"
        And I turn editing mode on
        And I add a "Open Studio" to section "1" and I fill the form with:
          | Name                         | Test Open Studio name 1      |
          | Description                  | Test Open Studio description |
          | Abuse reports are emailed to | teacher1@asd.com             |
          | ID number                    | OS1                          |
        And all users have accepted the plagarism statement for "OS1" openstudio
        And I follow "Test Open Studio name 1"
        And I click on "div.openstudio-upload-container" "css_element"
        And I press "Add web/embed link"
        And I set the following fields to these values:
          | Who can view this content | My module                                   |
          | Title                     | Test My Group Board View 1                  |
          | Description               | My Group Board View Description 1           |
          | Web link                  | https://www.youtube.com/watch?v=BGD6L-4yceY |
        And I press "Save"
        And I go to content edit view
        Then I should see "Test Open Studio name 1"
        And I should see "Web link"

        And I set the following fields to these values:
          | Who can view this content | My module                                   |
          | Title                     | Test My Group Board View modify 1           |
          | Description               | My Group Board View Description modify 1    |
          | Web link                  | https://www.youtube.com/watch?v=R4_rYoK4aLE |
        And I press "Save"
        And I follow "Shared content" in the openstudio navigation
        Then the "src" attribute of "div.openstudio-grid-item-content-preview img" "css_element" should contain "Youtube-61px"
        And I should see "Test My Group Board View modify 1"
        And I should not see "Test My Group Board View 1"

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
        And I am on site homepage
        And I follow "Course 1"
        And I turn editing mode on
        And I add a "Open Studio" to section "1" and I fill the form with:
            | Name | Test Open Studio name 1 |
            | Description | Test Open Studio description |
            | Your word for 'My Module' | Module 1 |
            | Group mode | Visible groups |
            | Grouping | grouping1 |
            | Enable pinboard | 99 |
            | Abuse reports are emailed to | teacher1@asd.com |
            | ID number                    | OS1              |
        And Open Studio test instance is configured for "Test Open Studio name 1"

    Scenario: Add new content just a title and description
        When I follow "Test Open Studio name 1"
        And I should see "Test Open Studio name 1"
        And I click on "div.openstudio-upload-container" "css_element"
        And I set the following fields to these values:
          | Who can view this content | My module |
          | Title | Test My Group Board View 1 |
          | Description | My Group Board View Description 1 |
        And I press "Add file"
        And I wait "1" seconds
        And I should see "Maximum size for new files"
        And I press "Add web/embed link"
        And I should not see "Maximum size for new files"
        And I press "Add web/embed link"
        And I should not see "Details"
        And I press "Save"
        And I click on "li.shared-content" "css_element"
        And I follow "Module 1"
        Then the "src" attribute of "div.openstudio-grid-item-content-preview img" "css_element" should contain "mod/openstudio/pix/openstudio_preview_image.png"

      Scenario: Add new content just a title and description with a file
        When I follow "Test Open Studio name 1"
        And I click on "div.openstudio-upload-container" "css_element"
        And I press "Add file"
        And I set the following fields to these values:
          | Who can view this content | My module |
          | Title | Test My Group Board View 2 |
          | Description | My Group Board View Description 2 |
          | Upload content | mod/openstudio/tests/importfiles/test2.jpg |
        And I press "Save"
        And I click on "li.shared-content" "css_element"
        And I follow "Module 1"
        Then the "src" attribute of "div.openstudio-grid-item-content-preview img" "css_element" should contain "test2.jpg"
        And I should see "Test My Group Board View 2"

      Scenario: Add new content just a title and description with an image including GPS and EXIF data
        When I follow "Test Open Studio name 1"
        And I click on "div.openstudio-upload-container" "css_element"
        And I press "Add file"
        And I set the following fields to these values:
          | Who can view this content | My module |
          | Title | Test My Group Board View 3 |
          | Description | My Group Board View Description 3 |
          | Upload content | mod/openstudio/tests/importfiles/test3.jpg |
          | Show GPS Data  | 1 |
          | Show Image Data | 1 |
        And I press "Save"
        And I click on "li.shared-content" "css_element"
        And I follow "Module 1"
        Then the "src" attribute of "div.openstudio-grid-item-content-preview img" "css_element" should contain "test3.jpg"
        And I should see "Test My Group Board View 3"

      Scenario: Add new content just a title and description with a file, ownership data
        When I follow "Test Open Studio name 1"
        And I click on "div.openstudio-upload-container" "css_element"
        And I press "Add file"
        And I set the following fields to these values:
          | Who can view this content | My module |
          | Title | Test My Group Board View 4 ownership All my own work |
          | Description | My Group Board View Description 4 ownership |
          | Upload content | mod/openstudio/tests/importfiles/test3.jpg |
        Then the "disabled" attribute of "div.felement input[name='ownershipdetail']" "css_element" should contain "disabled"
        And I press "Save"
        And I click on "li.shared-content" "css_element"
        And I follow "Module 1"
        Then the "src" attribute of "div.openstudio-grid-item-content-preview img" "css_element" should contain "test3.jpg"
        And I should see "Test My Group Board View 4 ownership All my own work"

        And I click on "div.openstudio-upload-container" "css_element"
        And I press "Add file"
        And I set the following fields to these values:
          | Who can view this content | My module |
          | Title | Test My Group Board View 4 ownership Found elsewhere |
          | Description | My Group Board View Description 3 ownership |
          | Upload content | mod/openstudio/tests/importfiles/test3.jpg |
          | Found elsewhere | 1 |
          | Details | Test 4 |
        And I press "Save"
        And I click on "li.shared-content" "css_element"
        And I follow "Module 1"
        Then the "src" attribute of "div.openstudio-grid-item-content-preview img" "css_element" should contain "test3.jpg"
        And I should see "Test My Group Board View 4 ownership Found elsewhere"

      Scenario: Add new content just a title and description with a file and tags
        When I follow "Test Open Studio name 1"
        And I click on "div.openstudio-upload-container" "css_element"
        And I press "Add file"
        And I set the following fields to these values:
          | Who can view this content | My module |
          | Title | Test My Group Board View 4 Tags |
          | Description | My Group Board View Description 4 Tags |
          | Upload content | mod/openstudio/tests/importfiles/test4.jpg |
          | Tags | Tests Add New Tags |
        And I wait "2" seconds
        And I should see "Tests Add New Tags"
        And I press "Save"
        And I click on "li.shared-content" "css_element"
        And I follow "Module 1"
        Then the "src" attribute of "div.openstudio-grid-item-content-preview img" "css_element" should contain "test4.jpg"
        And I should see "Test My Group Board View 4 Tags"

      Scenario: Add new content just a title and description with a weblink
        When I follow "Test Open Studio name 1"
        And I click on "div.openstudio-upload-container" "css_element"
        And I press "Add web/embed link"
        And I set the following fields to these values:
          | Who can view this content | My module |
          | Title | Test My Group Board View 5 Add web/embed link |
          | Description | My Group Board View Description 5 Add web/embed link |
          | Add web/embed link | https://www.youtube.com/watch?v=ktAnpf_nu5c |
        And I press "Save"
        And I click on "li.shared-content" "css_element"
        And I follow "Module 1"
        Then I should see "Test My Group Board View 5 Add web/embed link"

      Scenario: Add new content just a title and description with a weblink, ownership data
        When I follow "Test Open Studio name 1"
        And I click on "div.openstudio-upload-container" "css_element"
        And I press "Add web/embed link"
        And I set the following fields to these values:
          | Who can view this content | My module |
          | Title | Test My Group Board View 5 ownership All my own work |
          | Description | My Group Board View Description 5 ownership |
          | Add web/embed link | https://www.youtube.com/watch?v=Y7uGHY-t80I |
        Then the "disabled" attribute of "div.felement input[name='ownershipdetail']" "css_element" should contain "disabled"
        And I press "Save"
        And I click on "li.shared-content" "css_element"
        And I follow "Module 1"
        Then I should see "Test My Group Board View 5 ownership All my own work"

        And I click on "div.openstudio-upload-container" "css_element"
        And I press "Add web/embed link"
        And I set the following fields to these values:
          | Who can view this content | My module |
          | Title | Test My Group Board View 5 ownership Found elsewhere |
          | Description | My Group Board View Description 5 ownership |
          | Add web/embed link | https://www.youtube.com/watch?v=BGD6L-4yceY |
          | Found elsewhere | 1 |
          | Details | Test 5 |
        And I press "Save"
        And I click on "li.shared-content" "css_element"
        And I follow "Module 1"
        Then I should see "Test My Group Board View 5 ownership Found elsewhere"

      Scenario: Add new content just a title and description with a weblink and tags
        When I follow "Test Open Studio name 1"
        And I click on "div.openstudio-upload-container" "css_element"
        And I press "Add web/embed link"
        And I set the following fields to these values:
          | Who can view this content | My module |
          | Title | Test My Group Board View 5 Tags |
          | Description | My Group Board View Description 5 Tags |
          | Add web/embed link | https://www.youtube.com/watch?v=qyId4XZdC_4 |
          | Tags | Tests Add New Tags web/embed link |
        And I wait "2" seconds
        And I should see "Tests Add New Tags web/embed link"
        And I press "Save"
        And I click on "li.shared-content" "css_element"
        And I follow "Module 1"
        Then I should see "Test My Group Board View 5 Tags"

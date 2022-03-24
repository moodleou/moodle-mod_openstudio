@ou @ou_vle @mod @mod_openstudio @mod_openstudio_test_content @_file_upload @list_file_types @javascript
Feature: Create and edit Open Studio contents
When using Open Studio with other users
As a teacher
I need to create a content and upload a file
I should not see list of file types on Add File form

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
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
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
      | user     | group |
      | teacher1 | G1    |
      | student1 | G1    |
      | student2 | G1    |
      | teacher1 | G2    |
      | student2 | G2    |
      | student3 | G2    |
      | teacher1 | G3    |
      | student3 | G3    |
      | student4 | G3    |
    And the following open studio "instances" exist:
      | course | name                    | description                  | idnumber | reportingemail   |
      | C1     | Test Open Studio name 1 | Test Open Studio description | OS1      | teacher1@asd.com |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And I log in as "teacher1"

  Scenario: Edit content without upload file
    Given I am on "Course 1" course homepage
    And I follow "Test Open Studio name 1"
    And I follow "Add new content"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                 |
      | Title       | Test My Group Board View 1        |
      | Description | My Group Board View Description 1 |
    And I press "Save"
    And I go to content edit view
    Then I should see "Test Open Studio name 1"
    And I should see "Upload content"

    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                          |
      | Title       | Test My Group Board View modify 1          |
      | Description | My Group Board View Description modify 1   |
      | Files       | mod/openstudio/tests/importfiles/test1.jpg |
    And I press "Save"
    And I follow "Shared content" in the openstudio navigation
    Then I should see "Test My Group Board View modify 1"
    And the "src" attribute of "div.openstudio-grid-item-content-preview img" "css_element" should contain "test1.jpg"
    And I should not see "Test My Group Board View 1"

  Scenario: Edit content with upload file
    Given I am on "Course 1" course homepage
    And I follow "Test Open Studio name 1"
    And I follow "Add new content"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                          |
      | Title       | Test My Group Board View 1                 |
      | Description | My Group Board View Description 1          |
      | Files       | mod/openstudio/tests/importfiles/test1.jpg |
    And I press "Save"
    And I go to content edit view
    Then I should see "Test Open Studio name 1"
    And I should see "Upload content"
    And I should see "test1.jpg"
    And I set the following fields to these values:
      | My Module   | 1                                        |
      | Title       | Test My Group Board View modify 1        |
      | Description | My Group Board View Description modify 1 |
    And I press "Save"
    And I follow "Shared content" in the openstudio navigation
    Then I should see "Test My Group Board View modify 1"
    And the "src" attribute of "div.openstudio-grid-item-content-preview img" "css_element" should contain "test1.jpg"
    And I should not see "Test My Group Board View 1"

  Scenario: Edit content with unsupported file
    Given I am on "Course 1" course homepage
    And I follow "Test Open Studio name 1"
    And I follow "Add new content"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                                 |
      | Title       | Test My Group Board View 1                        |
      | Description | My Group Board View Description 1                 |
      | Files       | mod/openstudio/tests/importfiles/test.unsupported |
    And I press "Save"
    Then I should see "Some files (test.unsupported) cannot be uploaded"

    When I follow "Test Open Studio name 1"
    Then I should not see "test.unsupported"

  Scenario: Edit content without web link
    Given I am on "Course 1" course homepage
    And I follow "Test Open Studio name 1"
    And I follow "Add new content"
    And I press "Add web/embed link"
    And I set the following fields to these values:
      | My Module   | 1                                 |
      | Title       | Test My Group Board View 1        |
      | Description | My Group Board View Description 1 |
    And I press "Save"
    And I go to content edit view
    Then I should see "Test Open Studio name 1"
    And I should see "Add web/embed link"

    And I press "Add web/embed link"
    And I set the following fields to these values:
      | My Module   | 1                                           |
      | Title       | Test My Group Board View modify 1           |
      | Description | My Group Board View Description modify 1    |
      | Web link    | https://www.youtube.com/watch?v=BGD6L-4yceY |
    And I press "Save"
    And I follow "Shared content" in the openstudio navigation
    Then the "src" attribute of "img.openstudio-grid-item-thumbnail" "css_element" should contain "online_rgb_32px"
    And I should see "Test My Group Board View modify 1"
    And I should not see "Test My Group Board View 1"

  Scenario: Edit content with web link
    Given I am on "Course 1" course homepage
    And I follow "Test Open Studio name 1"
    And I follow "Add new content"
    And I press "Add web/embed link"
    And I set the following fields to these values:
      | My Module   | 1                                           |
      | Title       | Test My Group Board View 1                  |
      | Description | My Group Board View Description 1           |
      | Web link    | https://www.youtube.com/watch?v=BGD6L-4yceY |
    And I press "Save"
    And I go to content edit view
    Then I should see "Test Open Studio name 1"
    And I should see "Web link"

    And I set the following fields to these values:
      | My Module   | 1                                           |
      | Title       | Test My Group Board View modify 1           |
      | Description | My Group Board View Description modify 1    |
      | Web link    | https://www.youtube.com/watch?v=R4_rYoK4aLE |
    And I press "Save"
    And I follow "Shared content" in the openstudio navigation
    Then the "src" attribute of "img.openstudio-grid-item-thumbnail" "css_element" should contain "online_rgb_32px"
    And I should see "Test My Group Board View modify 1"
    And I should not see "Test My Group Board View 1"

  Scenario: Check list of file types hidden on Add File form
    Given I am on "Course 1" course homepage
    And Open Studio test instance is configured for "Test Open Studio name 1"
    When I follow "Test Open Studio name 1"
    And I follow "Upload content"
    And I press "Add file"
    Then I should not see "Accepted file types:"

  Scenario: Edit content with upload file and description file
    Given I follow "Manage private files..."
    And I upload "mod/openstudio/tests/importfiles/test2.jpg" file to "Files" filemanager
    And I click on "Save changes" "button"
    When I am on "Course 1" course homepage
    And I follow "Test Open Studio name 1"
    And I follow "Add new content"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                          |
      | Title       | Test My Group Board View 1                 |
      | Description | My Group Board View Description 1          |
      | Files       | mod/openstudio/tests/importfiles/test1.jpg |
    And I click on "Insert or edit image" "button"
    And I click on "Browse repositories..." "button"
        # Because of two file pickers we have to do very specific css selectors.
    And I click on "Private files" "link" in the ".moodle-dialogue.filepicker:not(.moodle-dialogue-hidden) .fp-repo-area" "css_element"
    And I click on "test2.jpg" "link"
    And I click on ".moodle-dialogue:not(.moodle-dialogue-hidden) .file-picker.fp-select .fp-select-confirm.btn-primary" "css_element"
    And I set the field "Describe this image for someone who cannot see it" to "An image"
    And I wait until the page is ready
    And I press "Save image"
    And I press "Save"
    Then "//img[contains(@src, 'pluginfile.php') and contains(@src, '/test2.jpg') and @alt='An image']" "xpath_element" should exist
    Given I go to content edit view
    Then I should see "Test Open Studio name 1"
    And I should see "Upload content"
    And I should see "test1.jpg"
    And "//img[contains(@src, 'user/draft') and contains(@src, '/test2.jpg') and @alt='An image']" "xpath_element" should exist
    And I set the following fields to these values:
      | My Module | 1                                 |
      | Title     | Test My Group Board View modify 1 |
    And I press "Save"
    And I follow "Shared content" in the openstudio navigation
    Then I should see "Test My Group Board View modify 1"
    And the "src" attribute of "div.openstudio-grid-item-content-preview img" "css_element" should contain "test1.jpg"
    And I should not see "Test My Group Board View 1"
    And I follow "Test My Group Board View modify 1"
    Then "//img[contains(@src, 'pluginfile.php') and contains(@src, '/test2.jpg') and @alt='An image']" "xpath_element" should exist

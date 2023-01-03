@ou @ou_vle @mod @mod_openstudio @mod_openstudio_manage_folders
Feature: Export my Open Studio contents
  In order to export my contents
  As a student
  I need to be able to export my contents

  Background: Setup course and studio
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category | format |
      | Course 1 | C1        | 0        | topics |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | teacher1 | C1     | manager |

    # Enable REST web service
    And I am logged in as "admin"
    And the following config values are set as admin:
      | enablewebservices | 1 |
      | enableportfolios  | 1 |

    And I navigate to "Server > Manage protocols" in site administration
    And I click on "Enable" "link" in the "REST protocol" "table_row"
    And I press "Save changes"
    And I navigate to "Plugins > Manage portfolios" in site administration
    And I set portfolio instance "File download" to "Enabled and visible"
    And I click on "Save" "button"

    # Create an Open Studio activity
    Given I am on the "Course 1" "Course" page logged in as "teacher1"
    And the following open studio "instances" exist:
      | course | name           | description                | pinboard | idnumber | tutorroles | enablefoldersanycontent |
      | C1     | Sharing Studio | Sharing Studio description | 99       | OS1      | manager    | 1                       |

    And the following open studio "contents" exist:
      | openstudio | user     | name      | description           | file                                       | visibility |
      | OS1        | teacher1 | Content 1 | Content Description 1 | mod/openstudio/tests/importfiles/test1.jpg | module     |
    And I wait "2" seconds
    And the following open studio "contents" exist:
      | openstudio | user     | name      | description           | file                                       | visibility |
      | OS1        | teacher1 | Content 2 | Content Description 2 | mod/openstudio/tests/importfiles/test2.jpg | module     |
    And the following open studio "contents" exist:
      | openstudio | user     | name                    | description | visibility |
      | OS1        | teacher1 | Content with empty slot |             | module     |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And I turn editing mode on

  @javascript
  Scenario: Export

    Given I am on the "Sharing Studio" "openstudio activity" page

    # Export all contents
    And I follow "My Content"
    And I follow "Export"
    And I press "All content shown"
    Then I should see "Downloading ..."

    # Export selected posts
    And I am on the "Sharing Studio" "openstudio activity" page
    And I follow "My Content"
    And I follow "Export"
    And I press "Selected posts"
    And I set the field "Content 1" to "1"
    And I press "Export selected posts"
    Then I should see "Downloading ..."

  @javascript
  Scenario: Export folder
    # Prepare data
    Given the following open studio "folders" exist:
      | openstudio | user     | name     | description          | visibility | contenttype    |
      | OS1        | teacher1 | Folder 1 | Folder Description 1 | module     | folder_content |
    And I am on the "Sharing Studio" "openstudio activity" page
    And I follow "My Content"
    And I follow "Folder 1"
    And I press "Select existing post to add to folder"
    And I click on "Select" "button" in the "Browse posts" "dialogue"
    And I click on "Save changes" "button" in the "Browse posts" "dialogue"
    And I should see "Content 2"

    # Do export
    And I follow "My Content"
    And I follow "Export"
    And I press "All content shown"
    Then I should see "Downloading ..."

  @javascript
  Scenario: Export python notebook file shows size
    Given the following open studio "contents" exist:
      | openstudio | user     | name   | description           | file                                        | visibility |
      | OS1        | teacher1 | Python | Content Description 2 | mod/openstudio/tests/importfiles/test.ipynb | module     |
    And I am on the "Sharing Studio" "openstudio activity" page
    And I follow "My Content"
    And I follow "Export"
    And I press "Selected posts"
    # Empty content is not include.
    And "//tr[4]" "xpath_element" should not exist
    Then I should see "111.09KB" in the "//label[text()='Python']/../following-sibling::td" "xpath_element"

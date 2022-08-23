@ou @ou_vle @mod @mod_openstudio @javascript
Feature: Delete my Open Studio content/other contents
  In order to delete my content
  As a teacher or manager
  I need to be able to delete my content
  As a teacher or manager with tutor role
  I need to be able to delete other user's contents

  Background: Setup course and studio
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | manager1 | Manager   | 1        | manager1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
      | manager1 | C1     | manager        |

    # Enable REST web service
    And I am logged in as "admin"
    And the following config values are set as admin:
      | enablewebservices | 1 |
    And I navigate to "Server > Manage protocols" in site administration
    And I click on "Enable" "link" in the "REST protocol" "table_row"
    And I press "Save changes"

    # Prepare a open studio
    And the following open studio "instances" exist:
      | course | name           | description                | pinboard | idnumber | tutorroles |
      | C1     | Sharing Studio | Sharing Studio description | 99       | OS1      | manager    |

    # Prepare open studio' contents and activities
    And the following open studio "contents" exist:
      | openstudio | user     | name              | description           | file                                       | visibility |
      | OS1        | student1 | Student content 1 | Content Description 1 | mod/openstudio/tests/importfiles/test1.jpg | module     |
      | OS1        | student1 | Student content 2 | Content Description 2 | mod/openstudio/tests/importfiles/test2.jpg | module     |
    And the following open studio "level1s" exist:
      | openstudio | name | sortorder |
      | OS1        | B1   | 1         |
    And the following open studio "level2s" exist:
      | level1 | name | sortorder |
      | B1     | A1   | 1         |
    And the following open studio "level3s" exist:
      | level2 | name | sortorder |
      | A1     | S1   | 1         |
    And the following open studio "folder templates" exist:
      | level3 | additionalcontents |
      | S1     | 2                  |
    And the following open studio "folder content templates" exist:
      | level3 | name            |
      | S1     | folder_template |
    And Open Studio levels are configured for "Sharing Studio"
    And all users have accepted the plagarism statement for "OS1" openstudio

  Scenario: Delete my content

    Given I am on the "Sharing Studio" "openstudio activity" page logged in as "student1"
    And I follow "Student content 1"
    And I press "Delete"
    And I click on "Delete" "button" in the "Delete post?" "dialogue"
    Then I should not see "Student content 1"

    # Can not delete other user's content wihout manage content permission
    And I am on the "Sharing Studio" "openstudio activity" page logged in as "teacher1"
    And I follow "Student content 2"
    Then I should not see "Delete"

    # Delete other user's contents wih manage content permission
    And I am on the "Sharing Studio" "openstudio activity" page logged in as "manager1"
    And I follow "Student content 2"
    And I press "Delete"
    And I click on "Delete" "button" in the "Delete post?" "dialogue"
    Then I should not see "Student content 2"

  @_file_upload
  Scenario: Delete my content in my activities

    # Delete content in my activity
    Given I am on the "Sharing Studio" "openstudio activity" page logged in as "student1"
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "a.openstudio-profile-mypaticipation" "css_element"
    And I wait until the page is ready
    And I follow "S1"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                          |
      | Title       | Student content 3                          |
      | Description | Student content 3 description              |
      | Files       | mod/openstudio/tests/importfiles/test1.jpg |
    And I press "Save"
    And I press "Delete"
    And I click on "Delete" "button" in the "Delete post?" "dialogue"

    # See post archive.
    And I press "Post archive"
    Then I should see "Student content 3"

  @_file_upload
  Scenario: Delete my content in my activities should navigate to content details when back to a deleted activity post
    # Delete content in my activity
    Given I am on the "Sharing Studio" "openstudio activity" page logged in as "student1"
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "a.openstudio-profile-mypaticipation" "css_element"
    And I wait until the page is ready
    And I follow "S1"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                          |
      | Title       | Student content 4                          |
      | Description | Student content 4 description              |
      | Files       | mod/openstudio/tests/importfiles/test1.jpg |
    And I press "Save"
    And I press "Delete"
    And I click on "Delete" "button" in the "Delete post?" "dialogue"
    And I follow "My Content > My Activities" in the openstudio navigation

    # Navigate to content details when back to a deleted activity post
    And I click on "a.openstudio-profile-mypaticipation" "css_element"
    And I wait until the page is ready
    And I follow "S1"
    Then I should see "S1"
    And I should see "Owner of this post"
    And "Add new comment" "button" should exist
    And "Request feedback" "button" should exist

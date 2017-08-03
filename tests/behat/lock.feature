@ou @ou_vle @mod @mod_openstudio
Feature: Lock/Unlock my content
  In order to lock/unlock my content
  As a teacher or manager
  I need to be able to lock/unlock my content
  As a teacher or manager with tutor role
  I need to be able to lock/unlock other user's contents

  Background: Setup course and studio
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | manager1 | Manager | 1 | manager1@asd.com |
      | student1 | Student | 1 | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | manager1 | C1     | manager        |
      | teacher1 | C1     | editingteacher |

    # Enable REST web service
    Then I log in as "admin"
    And the following config values are set as admin:
      | enablewebservices | 1 |
    And I navigate to "Plugins > Manage protocols" in site administration
    And I click on "Enable" "link" in the "REST protocol" "table_row"
    And I press "Save changes"

    And the following open studio "instances" exist:
      | course | name           | description                | pinboard | idnumber | tutorroles |
      | C1     | Sharing Studio | Sharing Studio description | 99       | OS1      | manager    |
    And the following open studio "contents" exist:
      | openstudio | user     | name            | description              | visibility |
      | OS1        | student1 | Student slot 1  | Test slot 1 description  | module     |
      | OS1        | manager1 | Manager slot 2  | Test slot 2 description  | module     |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And I log out

  @javascript
  Scenario: Lock/Unlock

    # Student (un)locks/ his content
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Sharing Studio"
    And I follow "Student slot 1"
    And I press "Lock"
    Then "Unlock" "button" should exist
    Then I should not see "0 Favourites"
    Then I should not see "0 Smiles"
    Then I should not see "0 Inspired"
    Then "Add new comment" "button" should not be visible
    Then "Edit" "button" should not be visible

    And I press "Unlock"
    Then I should see "0 Favourites"
    Then I should see "0 Smiles"
    Then I should see "0 Inspired"
    Then "Add new comment" "button" should be visible
    Then "Edit" "button" should be visible

    # Student can not lock other contents
    And I follow "Shared Content"
    And I follow "Manager slot 2"
    Then "Lock" "button" should not exist

    # Manager can (un)lock other contents
    And I am on site homepage
    And I log out
    And I log in as "manager1"
    And I am on "Course 1" course homepage
    And I follow "Sharing Studio"
    And I follow "Student slot 1"
    And I press "Lock"
    Then "Lock" "button" should not exist
    Then "Unlock" "button" should exist

    And I press "Unlock"
    Then "Lock" "button" should exist
    Then "Unlock" "button" should not exist

    # Hide Request Feedback button on Lock Content/Folder
    And I am on site homepage
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Sharing Studio"
    And I follow "Student slot 1"
    And I press "Request feedback"
    And I should see "Feedback requested" in the "div#openstudio_item_request_feedback" "css_element"
    And "Cancel feedback request" "button" should exist
    And I press "Lock"
    And "Request feedback" "button" should not exist
    And I press "Unlock"
    And "Cancel feedback request" "button" should exist
    And I should see "Feedback requested" in the "div#openstudio_item_request_feedback" "css_element"

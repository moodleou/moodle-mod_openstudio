@ou @ou_vle @mod @mod_openstudio @javascript
Feature: Open Studio guest access
  In order to Access open studio
  As a guest
  I want see a message prompting me to log in

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Student   | 1        | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | visibility |
      | Course 1 | C1        | 1          |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following open studio "instances" exist:
      | course | name         | description              | pinboard | idnumber | tutorroles | enablesubscriptions |
      | C1     | Guest studio | Guest studio description | 99       | OS1      | manager    | 1                   |
    And the following open studio "contents" exist:
      | openstudio | user     | name           | description             | visibility |
      | OS1        | student1 | Student slot 1 | Test slot 1 description | module     |
    And I am logged in as "admin"
    # Give guest the default student permissions so that they could view and post content if they weren't prevented.
    And I set the following system permissions of "Guest" role:
      | capability          | permission |
      | mod/openstudio:view | Allow      |
    And I am on the "Course 1" "enrolment methods" page
    And I click on "Enable" "link" in the "Guest access" "table_row"

  Scenario: Access Open Studio as a guest
    Given I am on the "Guest studio" "openstudio activity" page logged in as "guest"
    Then I should not see "Add new content"
    And I should not see "Student slot 1"
    And I should not see "My Content"
    And "Subscribe to my studio" "button" should not exist
    And "openstudio_navigation_notification" "region" should not exist
    And I should see "You may be able to view and post content here"

@ou @ou_vle @mod @mod_openstudio
Feature: Open Studio guest access
  In order to Access open studio
  As a guest
  I want see a message prompting me to log in

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Student   | 1        | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following open studio "instances" exist:
      | course | name         | description              | pinboard | idnumber | tutorroles | enablesubscriptions |
      | C1     | Guest studio | Guest studio description | 99       | OS1      | manager    | 1                   |
    And the following open studio "contents" exist:
      | openstudio | user     | name            | description              | visibility |
      | OS1        | student1 | Student slot 1  | Test slot 1 description  | module     |
    # Give guest the default student role so that they could view and post content if they weren't prevented.
    And the following config values are set as admin:
      | guestroleid | 5 |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Users > Enrolment methods" in current page administration
    And I click on "Enable" "link" in the "Guest access" "table_row"
    And I log out

  Scenario: Access Open Studio as a guest
    Given I log in as "guest"
    And I am on "Course 1" course homepage
    When I follow "Guest studio"
    Then I should not see "Add new content"
    And I should not see "Student slot 1"
    And I should not see "My Content"
    And "Subscribe to my studio" "button" should not exist
    And "openstudio_navigation_notification" "region" should not exist
    And I should see "You may be able to view and post content here"

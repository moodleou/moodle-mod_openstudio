@mod @mod_openstudio
Feature: Display the description in the course
  In order to display the the openstudio description in the course
  As a teacher
  I need to enable the 'Display description on course page' setting.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following open studio "instances" exist:
      | course | name            | intro                        | idnumber | tutorroles |
      | C1     | Test openstudio | Test Open Studio description | OS1      | manager    |

  Scenario: Show openstudio description in the course homepage
    Given I am on the "Test openstudio" "openstudio activity editing" page logged in as teacher1
    And the following fields match these values:
      | Display description on course page |  |
    And I set the following fields to these values:
      | Display description on course page | 1 |
    And I press "Save and return to course"
    When I am on "Course 1" course homepage
    Then I should see "Test Open Studio description"

  Scenario: Hide openstudio description in the course homepage
    Given I am on the "Test openstudio" "openstudio activity editing" page logged in as teacher1
    And the following fields match these values:
      | Display description on course page |  |
    And I press "Save and return to course"
    When I am on "Course 1" course homepage
    Then I should not see "Test Open Studio description"

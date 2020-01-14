@ou @ou_vle @mod @mod_openstudio @mod_openstudio_test_permission @mod_openstudio_test_import @mod_openstudio_test_export
Feature: Check permission import and export for student role in Open Studio
When using Open Studio with other users
As a admin
I stopped granting the rights to import and export for student

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Student   | 1        | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |

  @javascript
  Scenario: Permission setting by edit teacher and student not see Import and Export button in 'My Content' tab
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "OpenStudio 2 (pilot only)" to section "1" and I fill the form with:
      | Name        | Test Open Studio name 1      |
      | Description | Test Open Studio description |
      | ID number   | OS1                          |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And I set the following system permissions of "Student" role:
      | capability            | permission |
      | mod/openstudio:import | Prevent    |
      | mod/openstudio:export | Prevent    |
    And I log out
    And I am on site homepage
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test Open Studio name 1"
    When I follow "My Content"
    Then I should not see "Import"
    And I should not see "Export"

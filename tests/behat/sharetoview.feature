@ou @ou_vle @mod @mod_openstudio @mod_openstudio_share_to_view @javascript
Feature: Open Studio share to view setting is enable
  In order to Access open studio and share to view is enable
  As a student
  I want see a "Share to view is enable" banner

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
      | student2 | Student   | 2        | student2@asd.com |
    And the following "courses" exist:
      | fullname | shortname | visibility |
      | Course 1 | C1        | 1          |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "OpenStudio 2" to section "1" and I fill the form with:
      | Name                 | Test Open Studio name 1      |
      | Description          | Test Open Studio description |
      | ID number            | OS1                          |
      | Enable share to view | 1                            |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And I follow "Test Open Studio name 1"
    And I navigate to "Manage levels" in current page administration
    And I press "Add another Block"
    And I set the field "Block Name" to "Block 1"
    And I press "Save Changes"
    And I follow "Block 1"
    And I press "Add another Activity"
    And I set the field "Activity Name" to "Activity 1"
    And I press "Save Changes"
    And I follow "Activity 1"
    And I press "Add another Content"
    And I set the field "Content Name" to "Content 1.1 Required"
    And I press "Save Changes"
    And I log out

  Scenario: Show share to view banner
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test Open Studio name 1"
    And I follow "My Content > My Activities" in the openstudio navigation
    Then I should see "Activity 1"
    And I click on "a.openstudio-profile-mypaticipation" "css_element"
    And I follow "Content 1.1 Required"
    And I set the following fields to these values:
      | My Module   | 1                           |
      | Title       | Test My Activities View 1   |
      | Description | My Activities Description 1 |
    And I press "Save"
    And I should see "Test My Activities View 1"
    And I should not see "Share to view is enabled"
    And I follow "Shared Content" in the openstudio navigation
    And I should see "Test My Activities View 1"
    And I should see "Share to view is enabled"
    And I log out
    Then I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test Open Studio name 1"
    And I should not see "Test My Activities View 1"
    And I should see "Share to view is enabled"
    And I should not see "Test My Activities View 1"
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "a.openstudio-profile-mypaticipation" "css_element"
    And I follow "Content 1.1 Required"
    And I set the following fields to these values:
      | My Module   | 1                           |
      | Title       | Test My Activities View 2   |
      | Description | My Activities Description 2 |
    And I press "Save"
    And I should see "Test My Activities View 2"
    And I follow "Shared Content" in the openstudio navigation
    And I should see "Test My Activities View 2"
    And I should see "Test My Activities View 1"
    And I should see "Share to view is enabled"
    And I log out
    Then I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test Open Studio name 1"
    And I should see "Test My Activities View 1"
    And I should see "Test My Activities View 2"
    And I should not see "Share to view is enabled"
    And I navigate to "Settings" in current page administration
    And I follow "Expand all"
    And I set the field "Enable share to view" to "0"
    And I press "Save and display"
    And I log out
    Then I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test Open Studio name 1"
    And I should see "Test My Activities View 1"
    And I should see "Test My Activities View 2"
    And I should not see "Share to view is enabled"

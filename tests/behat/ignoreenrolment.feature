@ou @ou_vle @mod @mod_openstudio @mod_openstudio_ignoreenrolment @javascript
Feature: Test for new capability 'mod/openstudio:ignoreenrolment'
  Authenticate user access to OS2 without enrol

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Student1  | 1        | student1@asd.com |
      | student2 | Student2  | 2        | student2@asd.com |
    And the following "courses" exist:
      | fullname | shortname | visibility |
      | Course 1 | C1        | 1          |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "permission overrides" exist:
      | capability                     | permission | role | contextlevel | reference |
      | mod/openstudio:view            | Allow      | user | Course       | C1        |
      | mod/openstudio:viewothers      | Allow      | user | Course       | C1        |
      | mod/openstudio:addcontent      | Allow      | user | Course       | C1        |
      | mod/openstudio:addcomment      | Allow      | user | Course       | C1        |
      | mod/openstudio:sharewithothers | Allow      | user | Course       | C1        |
      | mod/openstudio:import          | Allow      | user | Course       | C1        |
      | mod/openstudio:export          | Allow      | user | Course       | C1        |
      | mod/openstudio:canlock         | Allow      | user | Course       | C1        |
      | mod/openstudio:ignoreenrolment | Allow      | user | Course       | C1        |
    And I log in as "admin" (in the OSEP theme)
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "OpenStudio 2" to section "1" and I fill the form with:
      | Name               | Guest studio             |
      | Description        | Guest studio description |
      | Enable 'My Module' | 1                        |
      | Enable pinboard    | 99                       |
      | Enable folders     | 1                        |
      | ID number          | OS1                      |
    And the following open studio "contents" exist:
      | openstudio | user     | name            | description             | visibility |
      | OS1        | student1 | Student1 slot 1 | Test slot 1 description | module     |
    And I am on "Course 1" course homepage
    And I navigate to "Users > Enrolment methods" in current page administration
    And I click on "Edit" "link" in the "Guest access" "table_row"
    And I set the following fields to these values:
      | Allow guest access | Yes |
    And I press "Save changes"
    And I log out

  Scenario: Authenticate user without enrol to course
    Given I log in as "student2" (in the OSEP theme)
    And I am on "Course 1" course homepage
    And I follow "Guest studio"
    And I press "Accept"
    And I follow "Upload content"
    And I set the following fields to these values:
      | Who can view this content | My module                            |
      | Title                     | Test content of student2             |
      | Description               | Test content of student2 description |
    And I press "Save"
    And I follow "Shared content" in the openstudio navigation
    Then I should see "Student1 slot 1"
    And I should see "Test content of student2"
    And I follow "Student1 slot 1"
    Then I should see "Test slot 1 description"
    And I follow "Shared content" in the openstudio navigation
    And ".openstudio-grid-item-content-detail-owner-view" "css_element" should exist
    And I click on ".openstudio-grid-item-content-detail-owner-view" "css_element"
    Then I should see "Student1 slot 1"

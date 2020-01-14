@ou @ou_vle @mod @mod_openstudio @mod_openstudio_test_share_tutor @javascript
Feature: Open Studio share with tutor filter
When using Open Studio to share content with my tutor
As a tutor
I can find out that content
When I select checkbox: "Shared with tutor"
And Apply the filter

  Background: Setup course and studio
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "groups" exist:
      | name   | course | idnumber |
      | group1 | C1     | G1       |
    And the following "groupings" exist:
      | name      | course | idnumber |
      | grouping1 | C1     | GI1      |
    And the following "grouping groups" exist:
      | grouping | group |
      | GI1      | G1    |
    And the following "group members" exist:
      | user     | group |
      | teacher1 | G1    |
      | student1 | G1    |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "OpenStudio 2 (pilot only)" to section "1" and I fill the form with:
      | Name        | Test Open Studio name 1      |
      | Description | Test Open Studio description |
      | Group mode  | Visible groups               |
      | Grouping    | grouping1                    |
      | ID number   | OS2                          |
      | Teacher     | 1                            |
    And I turn editing mode off
    And all users have accepted the plagarism statement for "OS2" openstudio

  Scenario: Can see "Shared with tutor" filter in My group
    Given I am on "Course 1" course homepage
    And I follow "Test Open Studio name 1"
    And I follow "Shared Content"
    And I click on "My Group" "link"
    And I click on "filter" "link"
    Then I should see "Shared with tutor"

  Scenario: "Shared with tutor" functionality
    Given I am on "Course 1" course homepage
    And I follow "Test Open Studio name 1"
    And I click on ".openstudio-add-new a" "css_element"
    And I select "My tutor" from the "Who can view this content" singleselect
    And I set the following fields to these values:
      | Title       | Studio content 1 |
      | Description | Description 1    |
    And I press "Save"
    When I follow "Shared Content"
    And I click on "My Group" "link"
    And I click on "filter" "link"
    And I set the following fields to these values:
      | Smile | 1 |
    And I press "Apply"
    And I should not see "Studio content 1"
    And I set the following fields to these values:
      | Smile             | 0 |
      | Shared with tutor | 1 |
    And I press "Apply"
    Then I should see "Studio content 1"

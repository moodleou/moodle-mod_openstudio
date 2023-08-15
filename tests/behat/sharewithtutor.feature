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
    And I am on the "Course 1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I add a "OpenStudio 2" to section "1" and I fill the form with:
      | Name        | Test Open Studio name 1      |
      | Description | Test Open Studio description |
      | Group mode  | Visible groups               |
      | Grouping    | grouping1                    |
      | ID number   | OS2                          |
      | Teacher     | 1                            |
    And I turn editing mode off
    And all users have accepted the plagarism statement for "OS2" openstudio

  Scenario: Can see "Shared with tutor" filter in My group
    Given I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "Shared Content"
    And I click on "My Group" "link"
    # Clicking on id='filter' (empty content, not visible) no longer works because the icon no longer has alt.
    And I click on "//a[@data-target='#filter_container']" "xpath_element"
    Then I should see "Shared with tutor"

  Scenario: "Shared with tutor" functionality
    Given I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "Add new content"
    And I set the field "My Tutor" to "1"
    And I set the following fields to these values:
      | Title       | Studio content 1 |
      | Description | Description 1    |
    And I press "Save"
    When I follow "Shared Content"
    And I click on "My Group" "link"
    And I click on "//a[@data-target='#filter_container']" "xpath_element"
    And I set the following fields to these values:
      | Smile | 1 |
    And I press "Apply"
    And I should not see "Studio content 1"
    And I set the following fields to these values:
      | Smile             | 0 |
      | Shared with tutor | 1 |
    And I press "Apply"
    Then I should see "Studio content 1"

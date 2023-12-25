@ou @ou_vle @mod @mod_openstudio @mod_openstudio_share_with_all_group @javascript
Feature: Create Open Studio contents
When using Open Studio with other users
As a teacher
I need to create content to share with groups where I am a member.

  Background: Setup course and studio
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
      | student2 | Student   | 2        | student2@asd.com |
      | student3 | Student   | 3        | student3@asd.com |
      | student4 | Student   | 4        | student4@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
    And the following "groups" exist:
      | name   | course | idnumber |
      | group1 | C1     | G1       |
      | group2 | C1     | G2       |
      | group3 | C1     | G3       |
    And the following "groupings" exist:
      | name      | course | idnumber |
      | grouping1 | C1     | GI1      |
    And the following "grouping groups" exist:
      | grouping | group |
      | GI1      | G1    |
      | GI1      | G2    |
      | GI1      | G3    |
    And the following "group members" exist:
      | user     | group |
      | teacher1 | G1    |
      | student1 | G1    |
      | student4 | G1    |
      | student2 | G2    |
      | teacher1 | G2    |
      | student3 | G3    |
    And the following open studio "instances" exist:
      | course | name                    | description                  | pinboard | idnumber | groupmode | grouping | pinboard | reportingemail   |
      | C1     | Test Open Studio name 1 | Test Open Studio description | 99       | OS1      | 2         | GI1      | 99       | teacher1@asd.com |
    And all users have accepted the plagarism statement for "OS1" openstudio

  Scenario: View content share with all groups.
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I should see "Test Open Studio name 1"
    And I follow "Add new content"
    And I set the following fields to these values:
      | All My Tutor Groups | 1                                 |
      | Title               | Test My Group Board View 1        |
      | Description         | My Group Board View Description 1 |
    And I press "Save"
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "student1"
    And I follow "Shared content > My Group" in the openstudio navigation
    And I should see "Test My Group Board View 1"
    And I follow "Test My Group Board View 1"
    And I should see "My Group Board View Description 1"
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "student2"
    And I follow "Shared content > My Group" in the openstudio navigation
    And I should see "Test My Group Board View 1"
    And I follow "Test My Group Board View 1"
    And I should see "My Group Board View Description 1"
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "student3"
    And I follow "Shared content > My Group" in the openstudio navigation
    # User three can view content thumbnails but can not interact.
    And I should see "Test My Group Board View 1"
    Then "//*[@class='openstudio-grid-item'][1]//img[contains(@src, 'comments_grey_rgb_32px')]" "xpath_element" should not exist

  Scenario: View comments in content share with all groups.
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I should see "Test Open Studio name 1"
    And I follow "Add new content"
    And I set the following fields to these values:
      | All My Tutor Groups | 1                                 |
      | Title               | Test My Group Board View 1        |
      | Description         | My Group Board View Description 1 |
    And I press "Save"
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "student1"
    And I follow "Shared content > My Group" in the openstudio navigation
    And I follow "Test My Group Board View 1"
    And I press "Add new comment"
    And I set the field "Comment" to "Student 1 Comment text"
    And I wait until the page is ready
    And I press "Post comment"
    And I should see "Student 1 Comment text"
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "student4"
    And I follow "Shared content > My Group" in the openstudio navigation
    And I follow "Test My Group Board View 1"
    # Student 4 can view comments of student 1.
    And I should see "Student 1 Comment text"
    And I press "Add new comment"
    And I set the field "Comment" to "Student 4 Comment text"
    And I wait until the page is ready
    And I press "Post comment"
    And I should see "Student 4 Comment text"
    # Student 2 is not in the same group as Student 1 and Student 4 cannot see comments.
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "student2"
    And I follow "Shared content > My Group" in the openstudio navigation
    And I follow "Test My Group Board View 1"
    And I should not see "Student 1 Comment text"
    Then I should not see "Student 4 Comment text"

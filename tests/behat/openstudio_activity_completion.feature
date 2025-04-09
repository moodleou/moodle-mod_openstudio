@mod @mod_openstudio @core_completion @javascript
Feature: View activity completion information in the openstudio activity
  In order to have visibility of openstudio completion requirements
  As a student
  I need to be able to view my openstudio completion progress

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
      | student2 | Student   | 2        | student2@asd.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion | showcompletionconditions |
      | Course 1 | C1        | 1                | 1                        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | teacher1 | C1     | editingteacher |
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
      | student2 | G1    |

  Scenario: View openstudio automatic completion.
    Given the following open studio "instances" exist:
      | course | name                    | description                  | pinboard | idnumber | groupmode | grouping | pinboard | reportingemail   | completion | completionview |
      | C1     | Test Open Studio name 1 | Test Open Studio description | 99       | OS1      | 2         | GI1      | 99       | teacher1@asd.com | 2          | 1              |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as teacher1
    Then "Test Open Studio name 1" should have the "View" completion condition
    # Student view.
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And the "View" completion condition of "Test Open Studio name 1" is displayed as "done"

  Scenario: View openstudio manual completion.
    Given the following open studio "instances" exist:
      | course | name                    | description                  | pinboard | idnumber | groupmode | grouping | pinboard | reportingemail   | completion | completionview |
      | C1     | Test Open Studio name 1 | Test Open Studio description | 99       | OS1      | 2         | GI1      | 99       | teacher1@asd.com | 2          | 1              |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And I am on the "Test Open Studio name 1" "openstudio activity editing" page logged in as teacher1
    And I set the following fields to these values:
      | Students must manually mark the activity as done | 1 |
      | id_tutorrolesgroup_1                             | 1 |
    And I press "Save and display"
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    # Teacher view.
    And the manual completion button for "Test Open Studio name 1" should be disabled
    # Student view.
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    Then the manual completion button of "Test Open Studio name 1" is displayed as "Mark as done"
    And I toggle the manual completion state of "Test Open Studio name 1"
    And the manual completion button of "Test Open Studio name 1" is displayed as "Done"

  Scenario: Openstudio custom completion completionposts create contents.
    Given the following open studio "instances" exist:
      | course | name                    | description                  | pinboard | idnumber | groupmode | grouping | pinboard | reportingemail   | completion | completionposts |
      | C1     | Test Open Studio name 1 | Test Open Studio description | 99       | OS1      | 2         | GI1      | 99       | teacher1@asd.com | 2          | 1               |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as teacher1
    And "Test Open Studio name 1" should have the "Make contents: 1" completion condition
    # Student view.
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And "Test Open Studio name 1" should have the "Make contents: 1" completion condition
    And I follow "Add new content"
    And I set the following fields to these values:
      | My Module   | 1                                 |
      | Title       | Test My Group Board View 1        |
      | Description | My Group Board View Description 1 |
    And I press "Save"
    # Check student has completed completion.
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And the "Make contents: 1" completion condition of "Test Open Studio name 1" is displayed as "done"
    # Login as teacher and delete content, student completion will be marked as not done.
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as teacher1
    And I follow "Test My Group Board View 1"
    And I press "Delete"
    And I click on "Delete" "button" in the "Delete post?" "dialogue"
    Then I should not see "Test My Group Board View 1"
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And "Test Open Studio name 1" should have the "Make contents: 1" completion condition

  Scenario: Openstudio custom completion completionposts create contents exclude folders.
    Given the following open studio "instances" exist:
      | course | name                    | description                  | pinboard | idnumber | groupmode | grouping | pinboard | reportingemail   | enablefolders | completion | completionposts |
      | C1     | Test Open Studio name 1 | Test Open Studio description | 99       | OS1      | 2         | GI1      | 99       | teacher1@asd.com | 1             | 2          | 2               |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as teacher1
    And "Test Open Studio name 1" should have the "Make contents: 2" completion condition
    And I should see "Create new folder"
    And I should see "Add new content"
    # Student view.
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And "Test Open Studio name 1" should have the "Make contents: 2" completion condition
    # Creating a folder.
    And I follow "Create new folder"
    And I set the following fields to these values:
      | My Module          | 1                            |
      | Folder title       | Test my folder view 1        |
      | Folder description | My folder view description 1 |
    And I press "Create folder"
    # Test we don't count folder.
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And "Test Open Studio name 1" should have the "Make contents: 2" completion condition
    And I follow "Upload content"
    And I set the following fields to these values:
      | My Module   | 1                                 |
      | Title       | Test My Group Board View 1        |
      | Description | My Group Board View Description 1 |
    And I press "Save"
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And "Test Open Studio name 1" should have the "Make contents: 2" completion condition
    And I follow "Upload content"
    And I set the following fields to these values:
      | My Module   | 1                                 |
      | Title       | Test My Group Board View 2        |
      | Description | My Group Board View Description 2 |
    And I press "Save"
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And the "Make contents: 2" completion condition of "Test Open Studio name 1" is displayed as "done"
    # Login as teacher and delete content, student completion will be marked as not done.
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as teacher1
    And I follow "Test My Group Board View 2"
    And I press "Delete"
    And I click on "Delete" "button" in the "Delete post?" "dialogue"
    Then I should not see "Test My Group Board View 2"
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And "Test Open Studio name 1" should have the "Make contents: 2" completion condition

  Scenario: Openstudio custom completion completioncomments create comment of content.
    Given the following open studio "instances" exist:
      | course | name                    | description                  | pinboard | idnumber | groupmode | grouping | pinboard | reportingemail   | completion | completioncomments |
      | C1     | Test Open Studio name 1 | Test Open Studio description | 99       | OS1      | 2         | GI1      | 99       | teacher1@asd.com | 2          | 1                  |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And the following open studio "contents" exist:
      | openstudio | user     | name      | description       | visibility |
      | OS1        | student1 | Content 1 | lorem ipsum dolor | module     |
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as teacher1
    And "Test Open Studio name 1" should have the "Make comments: 1" completion condition
    # Student view.
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And "Test Open Studio name 1" should have the "Make comments: 1" completion condition
    And I follow "Content 1"
    And I press "Add new comment"
    And I set the field "Comment" to "Test comment"
    And I press "Post comment"
    # Check student has completed completion.
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And the "Make comments: 1" completion condition of "Test Open Studio name 1" is displayed as "done"
    # Login as teacher and delete content, student completion will be marked as not done.
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as teacher1
    And I follow "Content 1"
    And I press "Delete"
    And I click on "Delete" "button" in the "Delete post?" "dialogue"
    Then I should not see "Content 1"
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And "Test Open Studio name 1" should have the "Make comments: 1" completion condition

  Scenario: Openstudio custom completion completioncomments user 1 reply and got deleted by user 2.
    Given the following open studio "instances" exist:
      | course | name                    | description                  | pinboard | idnumber | groupmode | grouping | pinboard | reportingemail   | completion | completioncomments |
      | C1     | Test Open Studio name 1 | Test Open Studio description | 99       | OS1      | 2         | GI1      | 99       | teacher1@asd.com | 2          | 1                  |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And the following open studio "contents" exist:
      | openstudio | user     | name                       | description       | visibility |
      | OS1        | student1 | Test My Group Board View 1 | lorem ipsum dolor | module     |
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student2
    And "Test Open Studio name 1" should have the "Make comments: 1" completion condition
    # Create a comment.
    And I follow "Test My Group Board View 1"
    And I press "Add new comment"
    And I set the field "Comment" to "Test root comment"
    And I press "Post comment"
    And I should see "Test root comment"
    # Student 1 view.
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And "Test Open Studio name 1" should have the "Make comments: 1" completion condition
    And I follow "Test My Group Board View 1"
    And I press "Reply"
    And I set the field "Comment" to "Comment text reply of student 1"
    And I press "Post comment"
    And I should see "Comment text reply of student 1"
    # Check student has completed completion.
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And the "Make comments: 1" completion condition of "Test Open Studio name 1" is displayed as "done"
    # Login as teacher1 and delete root comment, student1 completion will be marked as not done.
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as admin
    And I follow "Test My Group Board View 1"
    And I wait until the page is ready
    And I should see "Test root comment"
    And I should see "Comment text reply of student 1"
    And I click on "//span[contains(@class, 'openstudio-comment-delete-long-link')][1]" "xpath_element"
    And I click on "Delete" "button" in the "Delete comment" "dialogue"
    And I should not see "Test root comment"
    And I should not see "Comment text reply of student 1"
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    Then "Test Open Studio name 1" should have the "Make comments: 1" completion condition

  Scenario: Openstudio custom completion completionpostscomments create contents, comment of content.
    Given the following open studio "instances" exist:
      | course | name                    | description                  | pinboard | idnumber | groupmode | grouping | pinboard | reportingemail   | completion | completionpostscomments |
      | C1     | Test Open Studio name 1 | Test Open Studio description | 99       | OS1      | 2         | GI1      | 99       | teacher1@asd.com | 2          | 3                       |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as teacher1
    And "Test Open Studio name 1" should have the "Make contents and comments: 3" completion condition
    # Student view.
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And "Test Open Studio name 1" should have the "Make contents and comments: 3" completion condition
    And I follow "Add new content"
    And I set the following fields to these values:
      | My Module   | 1                                 |
      | Title       | Test My Group Board View 1        |
      | Description | My Group Board View Description 1 |
    And I press "Save"
    # Create comment 1.
    And I press "Add new comment"
    And I set the field "Comment" to "Test comment 1"
    And I press "Post comment"
    # Create comment 2.
    And I press "Add new comment"
    And I set the field "Comment" to "Test comment 2"
    And I press "Post comment"
    # Check student has completed completion.
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And the "Make contents and comments: 3" completion condition of "Test Open Studio name 1" is displayed as "done"

  Scenario: Openstudio custom completion completionposts with completionwordcountmin and completionwordcountmax.
    Given the following open studio "instances" exist:
      | course | name                    | description                  | pinboard | idnumber | groupmode | grouping | pinboard | reportingemail   | completion | completionposts | completionwordcountmin | completionwordcountmax |
      | C1     | Test Open Studio name 1 | Test Open Studio description | 99       | OS1      | 2         | GI1      | 99       | teacher1@asd.com | 2          | 1               | 5                      | 10                     |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as teacher1
    And "Test Open Studio name 1" should have the "Make posts or comments with minimum word count: 5" completion condition
    And "Test Open Studio name 1" should have the "Make posts or comments with maximum word count: 10" completion condition
    And "Test Open Studio name 1" should have the "Make contents: 1" completion condition
    # Student view.
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And "Test Open Studio name 1" should have the "Make posts or comments with minimum word count: 5" completion condition
    And "Test Open Studio name 1" should have the "Make posts or comments with maximum word count: 10" completion condition
    And "Test Open Studio name 1" should have the "Make contents: 1" completion condition
    And I follow "Add new content"
    And I set the following fields to these values:
      | My Module   | 1                          |
      | Title       | Test My Group Board View 1 |
      | Description | Description 1              |
    And I press "Save"
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And the "Make posts or comments with minimum word count: 5" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And the "Make posts or comments with maximum word count: 10" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And the "Make contents: 1" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And I follow "Add new content"
    And I set the following fields to these values:
      | My Module   | 1                               |
      | Title       | Test My Group Board View 2      |
      | Description | Description 1 2 3 4 5 6 7 8 9 0 |
    And I press "Save"
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And the "Make posts or comments with minimum word count: 5" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And the "Make posts or comments with maximum word count: 10" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And the "Make contents: 1" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And I follow "Add new content"
    And I set the following fields to these values:
      | My Module   | 1                          |
      | Title       | Test My Group Board View 3 |
      | Description | Description 1 2 3 4 5      |
    And I press "Save"
    Then I am on the "Test Open Studio name 1" "openstudio activity" page
    And the "Make posts or comments with minimum word count: 5" completion condition of "Test Open Studio name 1" is displayed as "done"
    And the "Make posts or comments with maximum word count: 10" completion condition of "Test Open Studio name 1" is displayed as "done"
    And the "Make contents: 1" completion condition of "Test Open Studio name 1" is displayed as "done"

  Scenario: Openstudio custom completion completioncomments with completionwordcountmin and completionwordcountmax.
    Given the following open studio "instances" exist:
      | course | name                    | description                  | pinboard | idnumber | groupmode | grouping | pinboard | reportingemail   | completion | completioncomments | completionwordcountmin | completionwordcountmax |
      | C1     | Test Open Studio name 1 | Test Open Studio description | 99       | OS1      | 2         | GI1      | 99       | teacher1@asd.com | 2          | 1                  | 5                      | 10                     |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as teacher1
    And "Test Open Studio name 1" should have the "Make posts or comments with minimum word count: 5" completion condition
    And "Test Open Studio name 1" should have the "Make posts or comments with maximum word count: 10" completion condition
    And "Test Open Studio name 1" should have the "Make comments: 1" completion condition
    # Student view.
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And "Test Open Studio name 1" should have the "Make posts or comments with minimum word count: 5" completion condition
    And "Test Open Studio name 1" should have the "Make posts or comments with maximum word count: 10" completion condition
    And "Test Open Studio name 1" should have the "Make comments: 1" completion condition
    And I follow "Add new content"
    And I set the following fields to these values:
      | My Module   | 1                                 |
      | Title       | Test My Group Board View 1        |
      | Description | My Group Board View Description 1 |
    And I press "Save"
    And I press "Add new comment"
    And I set the field "Comment" to "Test comment 2"
    And I press "Post comment"
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And the "Make posts or comments with minimum word count: 5" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And the "Make posts or comments with maximum word count: 10" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And the "Make comments: 1" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And I follow "Test My Group Board View 1"
    And I press "Add new comment"
    And I set the field "Comment" to "Test comment 1 2 3 4 5 6 7 8 9 0"
    And I press "Post comment"
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And the "Make posts or comments with minimum word count: 5" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And the "Make posts or comments with maximum word count: 10" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And the "Make comments: 1" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And I follow "Test My Group Board View 1"
    And I press "Add new comment"
    And I set the field "Comment" to "Test comment 1 2 3 4"
    And I press "Post comment"
    Then I am on the "Test Open Studio name 1" "openstudio activity" page
    And the "Make posts or comments with minimum word count: 5" completion condition of "Test Open Studio name 1" is displayed as "done"
    And the "Make posts or comments with maximum word count: 10" completion condition of "Test Open Studio name 1" is displayed as "done"
    And the "Make comments: 1" completion condition of "Test Open Studio name 1" is displayed as "done"

  Scenario: Openstudio custom completion completionpostscomments with completionwordcountmin and completionwordcountmax.
    Given the following open studio "instances" exist:
      | course | name                    | description                  | pinboard | idnumber | groupmode | grouping | pinboard | reportingemail   | completion | completionpostscomments | completionwordcountmin | completionwordcountmax |
      | C1     | Test Open Studio name 1 | Test Open Studio description | 99       | OS1      | 2         | GI1      | 99       | teacher1@asd.com | 2          | 2                       | 5                      | 10                     |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as teacher1
    And "Test Open Studio name 1" should have the "Make posts or comments with minimum word count: 5" completion condition
    And "Test Open Studio name 1" should have the "Make posts or comments with maximum word count: 10" completion condition
    And "Test Open Studio name 1" should have the "Make contents and comments: 2" completion condition
    # Student view.
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And "Test Open Studio name 1" should have the "Make posts or comments with minimum word count: 5" completion condition
    And "Test Open Studio name 1" should have the "Make posts or comments with maximum word count: 10" completion condition
    And "Test Open Studio name 1" should have the "Make contents and comments: 2" completion condition
    And I follow "Add new content"
    And I set the following fields to these values:
      | My Module   | 1                                 |
      | Title       | Test My Group Board View 1        |
      | Description | My Group Board View Description 1 |
    And I press "Save"
    And I press "Add new comment"
    And I set the field "Comment" to "Test comment 2"
    And I press "Post comment"
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And the "Make posts or comments with minimum word count: 5" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And the "Make posts or comments with maximum word count: 10" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And the "Make contents and comments: 2" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And I follow "Test My Group Board View 1"
    And I press "Add new comment"
    And I set the field "Comment" to "Test comment 1 2 3 4 5 6 7 8 9 0"
    And I press "Post comment"
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And the "Make posts or comments with minimum word count: 5" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And the "Make posts or comments with maximum word count: 10" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And the "Make contents and comments: 2" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And I follow "Test My Group Board View 1"
    And I press "Add new comment"
    And I set the field "Comment" to "Test comment 1 2 3 4"
    And I press "Post comment"
    Then I am on the "Test Open Studio name 1" "openstudio activity" page
    And the "Make posts or comments with minimum word count: 5" completion condition of "Test Open Studio name 1" is displayed as "done"
    And the "Make posts or comments with maximum word count: 10" completion condition of "Test Open Studio name 1" is displayed as "done"
    And the "Make contents and comments: 2" completion condition of "Test Open Studio name 1" is displayed as "done"

  Scenario: Openstudio custom completion restrict completion tracking posts in my activities section only.
    Given I am on the "Course 1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I add a openstudio activity to course "Course 1" section "1" and I fill the form with:
      | Name                         | Test Open Studio name 1      |
      | Description                  | Test Open Studio description |
      | Group mode                   | Visible groups               |
      | Grouping                     | grouping1                    |
      | Enable pinboard              | 99                           |
      | Enable folders               | 1                            |
      | Abuse reports are emailed to | teacher1@asd.com             |
      | ID number                    | OS1                          |
      | id_tutorrolesgroup_1         | 1                            |
    And the following open studio "level1s" exist:
      | openstudio | name   | sortorder |
      | OS1        | Block1 | 1         |
    And the following open studio "level2s" exist:
      | level1 | name      | sortorder |
      | Block1 | Activity1 | 1         |
    And the following open studio "level3s" exist:
      | level2    | name       | sortorder |
      | Activity1 | Content1.1 | 1         |
    And the following open studio "level3s" exist:
      | level2    | name      | sortorder | contenttype |
      | Activity1 | Folder1.1 | 1         | folder      |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Add requirements | 1 |
    And I should see "Restrict completion tracking to My Activities section only"
    # Check validation.
    And I set the following fields to these values:
      | Restrict completion tracking to My Activities section only | 1 |
    And I press "Save and display"
    And I should see "Please select at least one completion condition when \"Restrict completion tracking\" is enabled."
    And I set the following fields to these values:
      | Restrict completion tracking to My Activities section only | 1 |
      | id_completionpostsenabled                                  | 1 |
      | id_completionposts                                         | 2 |
    And I press "Save and display"

    # Test we don't count contents in My Pinboard.
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And "Test Open Studio name 1" should have the "Make contents: 2" completion condition
    And I follow "Upload content"
    And I set the following fields to these values:
      | My Module   | 1                         |
      | Title       | Test My Pinboard 1        |
      | Description | My Pinboard Description 1 |
    And I press "Save"
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    # Creating a folder.
    And I follow "Create new folder"
    And I set the following fields to these values:
      | My Module          | 1                            |
      | Folder title       | Test my folder view 1        |
      | Folder description | My folder view description 1 |
    And I press "Create folder"
    And I follow "Upload content to folder"
    And I set the following fields to these values:
      | Title       | Test Content Folder My Pinboard 2 |
      | Description | My Pinboard Description 2         |
    And I press "Save"
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And the "Make contents: 2" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And the "Make posts or comments in My Activities section only" completion condition of "Test Open Studio name 1" is displayed as "todo"
    # Test we count contents in My Activities only.
    And I follow "My Content > My Activities" in the openstudio navigation
    And I should see "Content1.1"
    And I should see "Folder1.1"
    And I click on "Content1.1" "link" in the ".openstudio-grid-item" "css_element"
    And I set the following fields to these values:
      | My Module   | 1                             |
      | Title       | Student content 1             |
      | Description | Student content 1 description |
    And I press "Save"
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "Folder1.1" "link" in the ".openstudio-add-new-folder" "css_element"
    And I follow "Upload content to folder"
    And I set the following fields to these values:
      | Title       | Student content folder 1             |
      | Description | Student content folder Description 1 |
    And I press "Save"
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    Then the "Make contents: 2" completion condition of "Test Open Studio name 1" is displayed as "done"
    And the "Make posts or comments in My Activities section only" completion condition of "Test Open Studio name 1" is displayed as "done"

  Scenario: Openstudio custom completion restrict completion tracking comments in my activities section only.
    Given I am on the "Course 1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I add a openstudio activity to course "Course 1" section "1" and I fill the form with:
      | Name                         | Test Open Studio name 1      |
      | Description                  | Test Open Studio description |
      | Group mode                   | Visible groups               |
      | Grouping                     | grouping1                    |
      | Enable pinboard              | 99                           |
      | Enable folders               | 1                            |
      | Abuse reports are emailed to | teacher1@asd.com             |
      | ID number                    | OS1                          |
      | id_tutorrolesgroup_1         | 1                            |
    And the following open studio "level1s" exist:
      | openstudio | name   | sortorder |
      | OS1        | Block1 | 1         |
    And the following open studio "level2s" exist:
      | level1 | name      | sortorder |
      | Block1 | Activity1 | 1         |
    And the following open studio "level3s" exist:
      | level2    | name       | sortorder |
      | Activity1 | Content1.1 | 1         |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Add requirements                                           | 1 |
      | Restrict completion tracking to My Activities section only | 1 |
      | id_completioncommentsenabled                               | 1 |
    And I press "Save and display"
      # Test we don't count content in My Module.
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And "Test Open Studio name 1" should have the "Make comments: 1" completion condition
    And I follow "Upload content"
    And I set the following fields to these values:
      | My Module   | 1                                 |
      | Title       | Test My Group Board View 1        |
      | Description | My Group Board View Description 1 |
    And I press "Save"
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And the "Make comments: 1" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And the "Make posts or comments in My Activities section only" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And I follow "My Content > My Activities" in the openstudio navigation
    And I should see "Content1.1"
    And I click on "Content1.1" "link" in the ".openstudio-grid-item" "css_element"
    And I set the following fields to these values:
      | My Module   | 1                             |
      | Title       | Student content 1             |
      | Description | Student content 1 description |
    And I press "Save"
    And I press "Add new comment"
    And I set the field "Comment" to "Test comment 1 2 3 4"
    And I press "Post comment"
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    Then the "Make comments: 1" completion condition of "Test Open Studio name 1" is displayed as "done"
    And the "Make posts or comments in My Activities section only" completion condition of "Test Open Studio name 1" is displayed as "done"

  Scenario: Openstudio custom completion restrict completion tracking posts and comments in my activities section only.
    Given I am on the "Course 1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I add a openstudio activity to course "Course 1" section "1" and I fill the form with:
      | Name                         | Test Open Studio name 1      |
      | Description                  | Test Open Studio description |
      | Group mode                   | Visible groups               |
      | Grouping                     | grouping1                    |
      | Enable pinboard              | 99                           |
      | Enable folders               | 1                            |
      | Abuse reports are emailed to | teacher1@asd.com             |
      | ID number                    | OS1                          |
      | id_tutorrolesgroup_1         | 1                            |
    And the following open studio "level1s" exist:
      | openstudio | name   | sortorder |
      | OS1        | Block1 | 1         |
    And the following open studio "level2s" exist:
      | level1 | name      | sortorder |
      | Block1 | Activity1 | 1         |
    And the following open studio "level3s" exist:
      | level2    | name       | sortorder |
      | Activity1 | Content1.1 | 1         |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Add requirements                                           | 1 |
      | Restrict completion tracking to My Activities section only | 1 |
      | id_completionpostscommentsenabled                          | 1 |
      | id_completionpostscomments                                 | 3 |
    And I press "Save and display"
      # Test we don't count content in My Module.
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And "Test Open Studio name 1" should have the "Make contents and comments: 3" completion condition
    And I follow "Upload content"
    And I set the following fields to these values:
      | My Module   | 1                                 |
      | Title       | Test My Group Board View 1        |
      | Description | My Group Board View Description 1 |
    And I press "Save"
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And the "Make contents and comments: 3" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And the "Make posts or comments in My Activities section only" completion condition of "Test Open Studio name 1" is displayed as "todo"
    And I follow "My Content > My Activities" in the openstudio navigation
    And I should see "Content1.1"
    And I click on "Content1.1" "link" in the ".openstudio-grid-item" "css_element"
    And I set the following fields to these values:
      | My Module   | 1                             |
      | Title       | Student content 1             |
      | Description | Student content 1 description |
    And I press "Save"
    # Create comment 1.
    And I press "Add new comment"
    And I set the field "Comment" to "Test comment 1"
    And I press "Post comment"
    # Create comment 2.
    And I press "Add new comment"
    And I set the field "Comment" to "Test comment 2"
    And I press "Post comment"
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    Then the "Make contents and comments: 3" completion condition of "Test Open Studio name 1" is displayed as "done"
    And the "Make posts or comments in My Activities section only" completion condition of "Test Open Studio name 1" is displayed as "done"

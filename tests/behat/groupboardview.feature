@ou @ou_vle @mod @mod_openstudio @mod_openstudio_test_content @_file_upload
Feature: Open Studio group stream

    Background: Setup course and studio
        Given the following "users" exist:
            | username | firstname | lastname | email |
            | teacher1 | Teacher | 1 | teacher1@asd.com |
            | student1 | Student | 1 | student1@asd.com |
            | student2 | Student | 2 | student2@asd.com |
            | student3 | Student | 3 | student3@asd.com |
            | student4 | Student | 4 | student4@asd.com |
        And the following "courses" exist:
            | fullname | shortname | category |
            | Course 1 | C1 | 0 |
        And the following "course enrolments" exist:
            | user | course | role |
            | teacher1 | C1 | editingteacher |
            | student1 | C1 | student |
            | student2 | C1 | student |
            | student3 | C1 | student |
            | student4 | C1 | student |
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
            | user     | group  |
            | teacher1 | G1 |
            | student1 | G1 |
            | student2 | G1 |
            | teacher1 | G2 |
            | student2 | G2 |
            | student3 | G2 |
            | student3 | G3 |
        And I log in as "teacher1"
        And I am on "Course 1" course homepage
        And I turn editing mode on
        And I add a "OpenStudio 2 (pilot only)" to section "1" and I fill the form with:
            | Name                         | Test Open Studio name 1      |
            | Description                  | Test Open Studio description |
            | Your word for 'My Module'    | Module 1                     |
            | Group mode                   | Separate groups              |
            | Grouping                     | grouping1                    |
            | Enable pinboard              | 99                           |
            | Abuse reports are emailed to | teacher1@asd.com             |
            | ID number                    | OS1                          |
        And Open Studio test instance is configured for "Test Open Studio name 1"
        And all users have accepted the plagarism statement for "OS1" openstudio

    @javascript
    Scenario: Test Open Studio My Groupboard View
        # Add new content view Group
        When I follow "Test Open Studio name 1"
        Then I should see "Test Open Studio name 1"
       And I follow "Add new content"
        And I press "Add file"
        And I set the following fields to these values:
          | Who can view this content | Group - group1                             |
          | Title                     | Test My Group Board View 1                 |
          | Description               | My Group Board View Description 1          |
          | Files                     | mod/openstudio/tests/importfiles/test1.jpg |
        And I press "Save"
        And I follow "Shared content > My Group" in the openstudio navigation
        Then I should see "Test My Group Board View 1"

        # Check that content posted in one group can be viewed by a user in another group when in Visible Groups mode
        And I am on site homepage
        And I log out
        And I log in as "student1"
        And I am on "Course 1" course homepage
        And I follow "Test Open Studio name 1"
        And I follow "Shared content > My Group" in the openstudio navigation
        Then I should see "Test My Group Board View 1"

        #  Check that content posted in one group cannot be viewed by a user in another group when in Separate Groups mode
        And I am on site homepage
        And I log out
        And I log in as "student3"
        And I am on "Course 1" course homepage
        And I follow "Test Open Studio name 1"
        And I follow "Shared content > My Group" in the openstudio navigation
        Then I should not see "Test My Group Board View 1"

        # Student add new content when in Separate Groups mode
       And I follow "Add new content"
        And I press "Add file"
        And I set the following fields to these values:
          | Who can view this content | Group - group3                             |
          | Title                     | Test My Group Board View 3                 |
          | Description               | My Group Board View Description 3          |
          | Files                     | mod/openstudio/tests/importfiles/test3.jpg |
        And I press "Save"
        And I follow "Shared content > My Group" in the openstudio navigation
        Then I should see "Test My Group Board View 3"

        # Check that a teacher can view content posted in any group when in Separate Groups mode
        And I am on site homepage
        And I log out
        And I log in as "teacher1"
        And I am on "Course 1" course homepage
        And I follow "Test Open Studio name 1"
        And I follow "Shared content > My Group" in the openstudio navigation
        Then I should see "Test My Group Board View 3"

        # Test that the list of groups (both for the filter and the edit content form) is generated correctly for users in different groups.
        And I am on site homepage
        And I log out
        And I log in as "student2"
        And I am on "Course 1" course homepage
        And I follow "Test Open Studio name 1"
        And I follow "Shared content > My Group" in the openstudio navigation
       And I follow "Add new content"
        And I press "Add file"
        And I set the following fields to these values:
          | Who can view this content | Group - group2                             |
          | Title                     | Test My Group Board View 2                 |
          | Description               | My Group Board View Description 2          |
          | Files                     | mod/openstudio/tests/importfiles/test2.jpg |
        And I press "Save"
        And I follow "Shared content > My Group" in the openstudio navigation
        Then I should see "Test My Group Board View 2"
        And I should see "Test My Group Board View 1"
        And I should not see "Test My Group Board View 3"

        And I am on site homepage
        And I log out
        And I log in as "student1"
        And I am on "Course 1" course homepage
        And I follow "Test Open Studio name 1"
        And I follow "Shared content > My Group" in the openstudio navigation
        Then I should see "Test My Group Board View 1"
        And I should not see "Test My Group Board View 2"
        And I should not see "Test My Group Board View 3"

        And I am on site homepage
        And I log out
        And I log in as "student3"
        And I am on "Course 1" course homepage
        And I follow "Test Open Studio name 1"
        And I follow "Shared content > My Group" in the openstudio navigation
        Then I should not see "Test My Group Board View 1"
        And I should see "Test My Group Board View 2"
        And I should see "Test My Group Board View 3"

        #  When groups mode is disabled, the My Groups page is not accessible, and content cannot be shared with a group
        And I am on site homepage
        And I log out
        And I log in as "teacher1"
        And I am on "Course 1" course homepage
        And I follow "Test Open Studio name 1"
        And I follow "Administration > Edit settings" in the openstudio navigation
        And I set the following fields to these values:
          | Group mode | No groups |
          | Grouping   | None      |
        And I press "Save and display"
        And I follow "Shared content" in the openstudio navigation
        Then I should see " Module 1"
        And I should not see "My Group"
        And I should not see "Test My Group Board View 1"
        And I should not see "Test My Group Board View 2"
        And I should not see "Test My Group Board View 3"

        # When groups mode is separate groups - Each group member can only see their own group, others are invisible
        And I follow "Administration > Edit settings" in the openstudio navigation
        And I set the following fields to these values:
          | Group mode | Separate groups      |
          | Grouping   | grouping1            |
        And I press "Save and display"
        And I follow "Shared content > My Group" in the openstudio navigation
        And I am on site homepage
        And I log out
        And I log in as "student1"
        And I am on "Course 1" course homepage
        And I follow "Test Open Studio name 1"
        And I follow "Shared content > My Group" in the openstudio navigation
        And I should see "group1" in the "span.openstudio-filter-select > span" "css_element"
        And "select#filter_groupid" "css_element" should not exist
        And I should see "Test My Group Board View 1"
        And I should not see "Test My Group Board View 2"

        # When groups mode is Visible groups - Each group member works in their own group, but can also see other groups
        And I am on site homepage
        And I log out
        And I log in as "teacher1"
        And I am on "Course 1" course homepage
        And I follow "Test Open Studio name 1"
        And I follow "Administration > Edit settings" in the openstudio navigation
        And I set the following fields to these values:
          | Group mode | Visible groups      |
        And I press "Save and display"
        And I am on site homepage
        And I log out
        And I log in as "student1"
        And I am on "Course 1" course homepage
        And I follow "Test Open Studio name 1"
        And I follow "Shared content > My Group" in the openstudio navigation
        And I should see "Test My Group Board View 1"
        And I should see "Test My Group Board View 2"
        And I should see "Test My Group Board View 3"
        And I follow "Test My Group Board View 2"
        Then I should see "My Module"
        And I should see "Test My Group Board View 2"
        And I should see "Owner of this post"
        And "Add new comment" "button" should exist

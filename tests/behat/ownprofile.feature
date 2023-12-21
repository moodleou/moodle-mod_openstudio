@ou @ou_vle @mod @mod_openstudio @mod_openstudio_manage_folders @javascript
Feature: Own profile view in Open Studio
When using Open Studio with other users
As a teacher
I need to create a content and upload a file

  Background: Setup course and studio
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
      | student2 | Student   | 2        | student2@asd.com |
      | student3 | Student   | 3        | student3@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
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
    And the following "group members" exist:
      | user     | group |
      | teacher1 | G1    |
      | student1 | G1    |
      | student2 | G1    |
      | teacher1 | G2    |
      | student2 | G2    |
      | student3 | G2    |
      | student3 | G3    |
    And I am on the "Course 1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I add a "OpenStudio 2" to section "1" and I fill the form with:
      | Name                         | Test Open Studio name 1      |
      | Description                  | Test Open Studio description |
      | Group mode                   | Visible groups               |
      | Grouping                     | grouping1                    |
      | Enable pinboard              | 99                           |
      | Enable folders               | 1                            |
      | Abuse reports are emailed to | teacher1@asd.com             |
      | ID number                    | OS1                          |
      | id_tutorrolesgroup_1         | 1                            |
    And I add a "OpenStudio 2" to section "1" and I fill the form with:
      | Name                         | Test Open Studio name 2      |
      | Description                  | Test Open Studio description |
      | Group mode                   | Visible groups               |
      | Grouping                     | grouping1                    |
      | Enable pinboard              | 99                           |
      | Enable folders               | 1                            |
      | Abuse reports are emailed to | teacher1@asd.com             |
      | ID number                    | OS2                          |
      | id_tutorrolesgroup_1         | 1                            |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And all users have accepted the plagarism statement for "OS2" openstudio
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I navigate to "Manage levels" in current page administration
    And I press "Add another Block"
    And I set the field "Block Name" to "Block 1"
    And I press "Save Changes"
    And I follow "Block 1"
    And I press "Add another Activity"
    And I set the field "Activity Name" to "Activity 1"
    And I press "Save Changes"
    And I press "Add another Activity"
    And I set the field "Activity Name" to "Activity 2"
    And I press "Save Changes"
    And I press "Add another Activity"
    And I set the field "Activity Name" to "Activity 3"
    And I press "Save Changes"
    And I follow "Activity 1"
    And I press "Add another Content"
    And I set the field "Content Name" to "Content 1.1 Required"
    And I set the field "Required (TMA)" to "1"
    And I press "Add another Content"
    And I set the field "Content Name" to "Content 1.2 Required"
    And I press "Add another Content"
    And I set the field "Content Name" to "Content 1.3 Required"
    And I press "Add another Content"
    And I follow "Manage levels"
    And I follow "Block 1"
    And I follow "Activity 2"
    And I press "Add another Content"
    And I set the field "Content Name" to "Content 2.1"
    And I press "Add another Content"
    And I set the field "Content Name" to "Content 2.2"
    And I press "Add another Content"
    And I set the field "Content Name" to "Content 2.3"
    And I press "Add another Content"
    And I follow "Manage levels"
    And I follow "Block 1"
    And I follow "Activity 3"
    And I press "Add another Content"
    And I set the field "Content Name" to "Content 3.1"
    And I press "Add another Content"
    And I set the field "Content Name" to "Content 3.2"
    And I press "Add another Content"
    And I set the field "Content Name" to "Content 3.3"
    And I press "Add another Content"
    And I set the field "Content Name" to "Content 3.5"
    And I press "Add another Content"

  Scenario: Check existing of View My Own Profile in My Activities/My Pinboard view
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And I navigate to "Settings" in current page administration
    And I follow "Expand all"
    And I set the following fields to these values:
      | Show Participation smiley | 1 |
    And I press "Save and display"
    And I follow "My Content > My Activities" in the openstudio navigation
    Then I should see "Teacher 1"
    And I should see "Participation"
    And I should see "My studio work progress"
    And I follow "My Content > My Pinboard" in the openstudio navigation
    Then I should see "Teacher 1" in the "#region-main" "css_element"
    And I should see "Participation"
    And I should see "My studio work progress"
    And I follow "Shared content > My Module" in the openstudio navigation
    Then I should not see "Teacher 1" in the "#region-main" "css_element"
    And I should not see "Participation"
    And I should not see "My studio work progress"
    And I follow "Shared content > My Group" in the openstudio navigation
    Then I should not see "Teacher 1" in the "#region-main" "css_element"
    And I should not see "Participation"
    And I should not see "My studio work progress"

    # switch other user
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "student1"
    And I follow "My content > My Activities" in the openstudio navigation
    Then I should see "Student 1" in the "#region-main" "css_element"
    And I should see "Participation"
    And I should see "My studio work progress"

  Scenario:  All users can expand or collapse the Profile Panel
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "My content > My Activities" in the openstudio navigation
    And I click on "a.openstudio-profile-mypaticipation" "css_element"
    Then I should see "comments made"
    And I should see "viewed"
    And I should see "Activity 1"
    And I should see "Activity 2"
    And I should see "Activity 3"

    # switch other user
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "student1"
    And I follow "My content > My Activities" in the openstudio navigation
    And I click on "a.openstudio-profile-mypaticipation" "css_element"
    Then I should see "comments made"
    And I should see "viewed"
    And I should see "Activity 1"
    And I should see "Activity 2"
    And I should see "Activity 3"

  @_file_upload
  Scenario: Check progress of user
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "My content > My Activities" in the openstudio navigation
    And I click on "a.openstudio-profile-mypaticipation" "css_element"
    And I click on "a.openstudio-profile-progress-step" "css_element"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                          |
      | Title       | Test My Ownprofile View                    |
      | Description | My Ownprofile View Description             |
      | Files       | mod/openstudio/tests/importfiles/test1.jpg |
    And I set the field "Describe this image for someone who cannot see it" to "This is image alt"
    And I press "Save"
    And I follow "My content > My Activities" in the openstudio navigation
    Then I should see "Test My Ownprofile View"

    And I click on "a.openstudio-profile-mypaticipation" "css_element"
    Then the "class" attribute of "a.openstudio-profile-progress-step" "css_element" should contain "block-active"
    And I should see "10%"

    # switch other user
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "student1"
    And I follow "My content > My Activities" in the openstudio navigation
    And I click on "a.openstudio-profile-mypaticipation" "css_element"
    And I click on "a.openstudio-profile-progress-step" "css_element"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                          |
      | Title       | Test My Ownprofile View Student            |
      | Description | Test My Ownprofile View Description        |
      | Files       | mod/openstudio/tests/importfiles/test1.jpg |
    And I set the field "Describe this image for someone who cannot see it" to "This is image alt"
    And I press "Save"
    And I follow "My content > My Activities" in the openstudio navigation
    Then I should see "Test My Ownprofile View Student"

    And I click on "a.openstudio-profile-mypaticipation" "css_element"
    Then the "class" attribute of "a.openstudio-profile-progress-step" "css_element" should contain "block-active"
    And I should see "10%"

  Scenario: Check own profile in case of empty content
    When I am on the "Test Open Studio name 2" "openstudio activity" page
    And I navigate to "Settings" in current page administration
    And I follow "Expand all"
    And I set the following fields to these values:
      | Show Participation smiley | 1 |
    And I press "Save and display"
    And I follow "My Content"
    And I should see "Participation"
    And I should not see "My studio work progress"

    # switch other user
    And I am on the "Test Open Studio name 2" "openstudio activity" page logged in as "student1"
    And I should not see "Participation"
    And I should not see "My studio work progress"

@ou @ou_vle @mod @mod_openstudio @mod_openstudio_folder_overview @javascript
Feature: Sharing level setting in activity folder

  Background: Setup course and studio
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
      | student2 | Student   | 2        | student2@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
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
      | level2    | name       | sortorder | contenttype |
      | Activity1 | Content1.1 | 1         | folder      |
    And the following open studio "folder templates" exist:
      | level3     | additionalcontents |
      | Content1.1 | 10                 |
    And all users have accepted the plagarism statement for "OS1" openstudio

  Scenario: Test the activity folder when the lowest sharing level is Private.
    Given I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Enable 'My Module' | 0       |
      | Sharing level      | 1,7,2,3 |
      | Enable 'My Module' | 1       |
    And I press "Save and display"
    Then I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "Content1.1" "link" in the ".openstudio-grid-item" "css_element"
    And the "src" attribute of ".openstudio-folder-view-title-background > .openstudio-folder-view-title-icon > img" "css_element" should contain "onlyme"

  Scenario: Test the activity folder when the lowest sharing level is Tutor.
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Enable 'My Module' | 0     |
      | Sharing level      | 7,2,3 |
      | Enable 'My Module' | 1     |
    And I press "Save and display"
    Then I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "Content1.1" "link" in the ".openstudio-grid-item" "css_element"
    And the "src" attribute of ".openstudio-folder-view-title-background > .openstudio-folder-view-title-icon > img" "css_element" should contain "share_with_tutor"

  Scenario: Test the activity folder when the lowest sharing level is Group.
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Enable 'My Module' | 0   |
      | Sharing level      | 2,3 |
      | Enable 'My Module' | 1   |
    And I press "Save and display"
    Then I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "Content1.1" "link" in the ".openstudio-grid-item" "css_element"
    And the "src" attribute of ".openstudio-folder-view-title-background > .openstudio-folder-view-title-icon > img" "css_element" should contain "share_with_my_group"

  Scenario: Test the activity folder when the lowest sharing level is My Module.
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Enable 'My Module' | 0 |
      | Sharing level      | 3 |
      | Enable 'My Module' | 1 |
    And I press "Save and display"
    Then I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "Content1.1" "link" in the ".openstudio-grid-item" "css_element"
    And the "src" attribute of ".openstudio-folder-view-title-background > .openstudio-folder-view-title-icon > img" "css_element" should contain "mymodule"

  Scenario: Test when setting no groups and the sharing level contains only Group.
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Enable 'My Module' | 0         |
      | Sharing level      | 2         |
      | Group mode         | No groups |
      | Enable 'My Module' | 1         |
    And I press "Save and display"
    Then I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "Content1.1" "link" in the ".openstudio-grid-item" "css_element"
    And the "src" attribute of ".openstudio-folder-view-title-background > .openstudio-folder-view-title-icon > img" "css_element" should contain "onlyme"

  Scenario: Test when setting no groups and the sharing level contains only Tutor.
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Enable 'My Module' | 0         |
      | Sharing level      | 7, 2      |
      | Group mode         | No groups |
      | Enable 'My Module' | 1         |
    And I press "Save and display"
    Then I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "Content1.1" "link" in the ".openstudio-grid-item" "css_element"
    And the "src" attribute of ".openstudio-folder-view-title-background > .openstudio-folder-view-title-icon > img" "css_element" should contain "onlyme"

  Scenario: Test when setting no groups and the sharing level contains Tutor and Group.
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Enable 'My Module' | 0         |
      | Sharing level      | 7, 2      |
      | Group mode         | No groups |
      | Enable 'My Module' | 1         |
    And I press "Save and display"
    Then I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "Content1.1" "link" in the ".openstudio-grid-item" "css_element"
    And the "src" attribute of ".openstudio-folder-view-title-background > .openstudio-folder-view-title-icon > img" "css_element" should contain "onlyme"

  Scenario: Test when setting no groups and the sharing level contains Tutor and Module.
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Enable 'My Module' | 0         |
      | Sharing level      | 7, 3      |
      | Group mode         | No groups |
      | Enable 'My Module' | 1         |
    And I press "Save and display"
    Then I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "Content1.1" "link" in the ".openstudio-grid-item" "css_element"
    And the "src" attribute of ".openstudio-folder-view-title-background > .openstudio-folder-view-title-icon > img" "css_element" should contain "mymodule"

  Scenario: Test when setting no groups and the sharing level contains Tutor, Group and Module.
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Enable 'My Module' | 0         |
      | Sharing level      | 7, 2, 3   |
      | Group mode         | No groups |
      | Enable 'My Module' | 1         |
    And I press "Save and display"
    Then I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "Content1.1" "link" in the ".openstudio-grid-item" "css_element"
    And the "src" attribute of ".openstudio-folder-view-title-background > .openstudio-folder-view-title-icon > img" "css_element" should contain "mymodule"

  Scenario: Test when setting no groups and the sharing level contains Group and Module.
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Enable 'My Module' | 0         |
      | Sharing level      | 2, 3      |
      | Group mode         | No groups |
      | Enable 'My Module' | 1         |
    And I press "Save and display"
    Then I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student1
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "Content1.1" "link" in the ".openstudio-grid-item" "css_element"
    And the "src" attribute of ".openstudio-folder-view-title-background > .openstudio-folder-view-title-icon > img" "css_element" should contain "mymodule"

  Scenario: Test when user isn't added to any group and the sharing level contains Group and Module.
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Enable 'My Module' | 0    |
      | Sharing level      | 2, 3 |
      | Enable 'My Module' | 1    |
    And I press "Save and display"
    Then I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student2
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "Content1.1" "link" in the ".openstudio-grid-item" "css_element"
    And the "src" attribute of ".openstudio-folder-view-title-background > .openstudio-folder-view-title-icon > img" "css_element" should contain "mymodule"

  Scenario: Test when user isn't added to any group and the sharing level contains only Group.
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Enable 'My Module' | 0 |
      | Sharing level      | 2 |
      | Enable 'My Module' | 1 |
    And I press "Save and display"
    Then I am on the "Test Open Studio name 1" "openstudio activity" page logged in as student2
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "Content1.1" "link" in the ".openstudio-grid-item" "css_element"
    And the "src" attribute of ".openstudio-folder-view-title-background > .openstudio-folder-view-title-icon > img" "css_element" should contain "onlyme"

  Scenario: Test when user Single sharing option should default to being selected.
    Given I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Enable 'My Module' | 0 |
      | Sharing level      | 2 |
      | Enable 'My Module' | 1 |
    And I press "Save and display"
    When I click on "Upload content" "text"
    Then the field "Only Me" matches value "1"
    And I set the following fields to these values:
      | Title       | Test My Group Board View 1        |
      | Description | My Group Board View Description 1 |
    And I press "Save"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Enable 'My Module' | 0         |
      | Sharing level      | 1,7,2,3   |
      | Group mode         | No groups |
      | Enable 'My Module' | 1         |
    And I press "Save and display"
    And I click on "Upload content" "text"
    And the field "My Module" matches value "0"
    And the field "My Tutor" matches value "0"
    And the field "Only Me" matches value "0"

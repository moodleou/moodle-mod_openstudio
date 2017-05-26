@ou @ou_vle @mod @mod_openstudio @mod_openstudio_test_folder @_file_upload @javascript
Feature: Create and edit Folder
    When using Open Studio with other users
    As a teacher
    I need to create a folder

    Background: Setup course and studio
        Given the following "users" exist:
            | username | firstname | lastname | email |
            | teacher1 | Teacher | 1 | teacher1@asd.com |
            | student1 | Student | 1 | student1@asd.com |
            | student2 | Student | 2 | student2@asd.com |
            | student3 | Student | 3 | student3@asd.com |
            | student4 | Student | 4 | student4@asd.com |
        And the following "courses" exist:
            | fullname | shortname | category | format      |
            | Course 1 | C1        | 0        | oustudyplan |
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
            | teacher1 | G3 |
            | student3 | G3 |
            | student4 | G3 |
        And I log in as "teacher1"
        And I am on site homepage
        And I follow "Course 1"
        And I turn editing mode on
        And I add a "OpenStudio 2 (pilot only)" to section "0" and I fill the form with:
            | Name                         | Test Open Studio name 1      |
            | Description                  | Test Open Studio description |
            | Your word for 'My Module'    | Module 1                     |
            | Group mode                   | Visible groups               |
            | Grouping                     | grouping1                    |
            | Enable pinboard              | 100                          |
            | Abuse reports are emailed to | teacher1@asd.com             |
            | ID number                    | OS1                          |
        And all users have accepted the plagarism statement for "OS1" openstudio

    Scenario: Enable folders in openstudio settings
        When I follow "Test Open Studio name 1"
        And I should see "You have 100 uploads available."
        And I should not see "Upload content"
        And I should not see "Create new folder"
        And I follow "Administration > Edit" in the openstudio navigation
        And I follow "Expand all"
        And I set the field "Enable Folders" to "1"
        And I press "Save and display"
        And I should see "Upload content"
        And I should see "Create new folder"

    Scenario: Enable Folder in other view
        When I follow "Test Open Studio name 1"
        And I should see "You have 100 uploads available."
        And I should not see "Upload content"
        And I should not see "Create new folder"
        And I follow "Administration > Edit" in the openstudio navigation
        And I follow "Expand all"
        And I set the field "Enable Folders" to "1"
        And I press "Save and display"
        And I should see "Upload content"
        And I should see "Create new folder"

        # Enable Folder in My Module
        And I follow "Shared content > My Module" in the openstudio navigation
        And I should see "Module 1"
        And I should see "Upload content"
        And I should see "Create new folder"
        And I should see "You have 100 uploads available"

        # Enable Folder in My Group
        And I follow "Shared content > My Group" in the openstudio navigation
        And I should see "My Group"
        And I should see "Upload content"
        And I should see "Create new folder"
        And I should see "You have 100 uploads available"

        # Enable Folder in My Pinboard
        And I follow "My content" in the openstudio navigation
        And I should see "My Pinboard"
        And I should see "Upload content"
        And I should see "Create new folder"
        And I should see "You have 100 uploads available"

        # Enable Folder in My Activities
        And I follow "Administration > Manage levels" in the openstudio navigation
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
        And I set the field "Required (TMA)" to "1"
        And I set the field "Is folder?" to "1"
        And I press "Add another Content"
        And I set the field "Content Name" to "Content 2"
        And I set the field "Required (TMA)" to "1"
        And I press "Add another Content"
        And I follow "Course 1"
        And I follow "Test Open Studio name 1"
        And I follow "My content > My Activities" in the openstudio navigation
        And I should see "Activity 1"
        And I should see "Content 1.1 Required"
        And I should see "Content 2"
        And the "src" attribute of "div.openstudio-folder-content img.openstudio-folder-tab" "css_element" should contain "openstudio_sets_preview_box"

    Scenario: Create Folder in other view
        Given I am on site homepage
        And I log out
        And I am using the OSEP theme
        And I log in as "teacher1" (in the OSEP theme)
        And I follow "Course 1"
        And I press "Expand all"
        When I follow "Test Open Studio name 1"
        And I follow "Administration > Edit" in the openstudio navigation
        And I follow "Expand all"
        And I set the field "Enable Folders" to "1"
        And I press "Save and display"

        # Create new folder in My Module view
        And I follow "Create new folder"
        Then "Create" "text" should exist in the ".breadcrumb-nav" "css_element"
        And I set the following fields to these values:
          | Who can view this folder  | My module                                  |
          | Folder title              | Test my folder view 1                      |
          | Folder description        | My folder view description 1               |
        And I press "Create folder"
        And I follow "Shared content > My Module" in the openstudio navigation
        And I should see "Test my folder view 1"
        And the "src" attribute of "div.openstudio-folder-content img.openstudio-folder-tab" "css_element" should contain "openstudio_sets_preview_box"

        # Create new folder in My Group view
        And I follow "Create new folder"
        And I set the following fields to these values:
          | Who can view this folder  | Group - group1                             |
          | Folder title              | Test my folder view 2                      |
          | Folder description        | My folder view description 2               |
        And I press "Create folder"
        And I follow "Shared content > My Group" in the openstudio navigation
        And I should see "Test my folder view 2"
        And the "src" attribute of "div.openstudio-folder-content img.openstudio-folder-tab" "css_element" should contain "openstudio_sets_preview_box"

        # Create new folder in My Pinboard view
        And I follow "Create new folder"
        And I set the following fields to these values:
          | Who can view this folder  | Only me                                    |
          | Folder title              | Test my folder view 3                      |
          | Folder description        | My folder view description 3               |
        And I press "Create folder"
        And I follow "My content" in the openstudio navigation
        And I should see "Test my folder view 3"
        And the "src" attribute of "div.openstudio-folder-content img.openstudio-folder-tab" "css_element" should contain "openstudio_sets_preview_box"
    
    Scenario: Edit Folder in other view
        Given I am on site homepage
        And I log out
        And I am using the OSEP theme
        And I log in as "teacher1" (in the OSEP theme)
        And I follow "Course 1"
        And I press "Expand all"
        When I follow "Test Open Studio name 1"
        And I follow "Administration > Edit" in the openstudio navigation
        And I follow "Expand all"
        And I set the field "Enable Folders" to "1"
        And I press "Save and display"

        # Create new folder in My Module view
        And I follow "Create new folder"
        And I set the following fields to these values:
          | Who can view this folder  | My module                                  |
          | Folder title              | Test my folder view 1                      |
          | Folder description        | My folder view description 1               |
        And I press "Create folder"

        # edit folder in My Module view
        And I go to content edit view
        And I follow "Edit folder title and permissions"
        Then "Test my folder view 1" "text" should exist in the ".breadcrumb-nav" "css_element"
        Then "Edit" "text" should exist in the ".breadcrumb-nav" "css_element"
        And I set the field "Folder title" to "Test my folder view 2"
        And I press "Save"
        And I follow "Shared content > My Module" in the openstudio navigation
        And I should not see "Test my folder view 1"
        And I should see "Test my folder view 2"

    Scenario: Breadcrumb navigation for Folder and Content of folder
        Given I am on site homepage
        And I log out
        And I am using the OSEP theme
        And I log in as "teacher1" (in the OSEP theme)
        And I follow "Course 1"
        And I press "Expand all"
        When I follow "Test Open Studio name 1"
        And I follow "Administration > Edit" in the openstudio navigation
        And I follow "Expand all"
        And I set the field "Enable Folders" to "1"
        And I press "Save and display"

        # Add new folder
        And the following open studio "folders" exist:
        | openstudio | user     | name                   | description                       | visibility | contenttype    |
        | OS1        | teacher1 | Test Folder Overview   | My Folder Overview Description 1  | module     | folder_content |
        And I follow "Test Open Studio name 1"
        And I follow "Shared Content > My Module" in the openstudio navigation
        And I follow "Test Folder Overview"
        And the OSEP theme breadcrumbs should be "C1 Home > Week 1 > Test Open Studio name 1 > My Pinboard > Test Folder Overview"

        # Add new content of folder
        And I follow "Add new content"
        And the OSEP theme breadcrumbs should be "C1 Home > Week 1 > Test Open Studio name 1 > My Pinboard > Test Folder Overview > Create"
        And I set the following fields to these values:
        | Title                     | Test Content Folder Overview                    |
        | Description               | My Folder Overview Description                  |
        And I press "Save"
        And the OSEP theme breadcrumbs should be "C1 Home > Week 1 > Test Open Studio name 1 > My Pinboard > Test Folder Overview > Test Content Folder Overview"

        # switch to student1
        And I log out (in the OSEP theme)
        And I log in as "student1" (in the OSEP theme)
        And I follow "Course 1"
        And I press "Expand all"
        And I follow "Test Open Studio name 1"
        And I follow "Shared content > My Module" in the openstudio navigation
        And I follow "Test Open Studio name 1"
        And I follow "Shared Content > My Module" in the openstudio navigation
        And I follow "Test Folder Overview"
        And the OSEP theme breadcrumbs should be "C1 Home > Week 1 > Test Open Studio name 1 > My Module >  Teacher's work > Test Folder Overview"
        And I follow "Test Content Folder Overview"
        Then the OSEP theme breadcrumbs should be "C1 Home > Week 1 > Test Open Studio name 1 > My Module >  Teacher's work > Test Folder Overview >  Test Content Folder Overview"

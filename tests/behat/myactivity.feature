@ou @ou_vle @mod @mod_openstudio @mod_openstudio_manage_folders @mod_openstudio_myactivity @javascript
Feature: My Activity view in Open Studio
When using Open Studio with other users
As a teacher
I need to create a content and upload a file

  Background: Setup course and studio
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
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
    And all users have accepted the plagarism statement for "OS1" openstudio
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
    And I follow "Manage levels"
    And I follow "Block 1"
    And I follow "Activity 3"
    And I press "Add another Content"
    And I set the field "Content Name" to "Content 3.1"
    And I press "Add another Content"

  Scenario: Show My Activity Board View
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "My Content > My Activities" in the openstudio navigation
    Then I should see "Activity 1"
    Then I should see "Pre-defined activity slots set by module teams. Your shared posts will also be seen in corresponding shared content areas."
    And I should not see "Activity 2"
    And I should see "Activity 3"

  Scenario: Upload a new content without file upload
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "a.openstudio-profile-mypaticipation" "css_element"
    And I follow "Content 1.1 Required"
    And I set the following fields to these values:
      | My Module   | 1                           |
      | Title       | Test My Activities View 1   |
      | Description | My Activities Description 1 |
    And I press "Save"
    And I follow "My Content > My Activities" in the openstudio navigation
    And I should see "Test My Activities View 1"
    And I should see "Content 1.1 Required"
    Then the "src" attribute of "img.openstudio-grid-item-thumbnail" "css_element" should contain "online_rgb_32px"

  @_file_upload
  Scenario: Upload a new content with file upload
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "a.openstudio-profile-mypaticipation" "css_element"
    And I follow "Content 1.1 Required"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                          |
      | Title       | Test My Activities View 2                  |
      | Description | My Activities Description 2                |
      | Files       | mod/openstudio/tests/importfiles/test1.jpg |
    And I set the field "Describe this image for someone who cannot see it" to "This is image alt"
    And I press "Save"
    And I follow "My Content > My Activities" in the openstudio navigation
    And I should see "Test My Activities View 2"
    And I should see "Content 1.1 Required"
    Then the "src" attribute of "img.openstudio-grid-item-thumbnail" "css_element" should contain "test1.jpg"

  Scenario: Upload a new content with Add web/embed link
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "a.openstudio-profile-mypaticipation" "css_element"
    And I follow "Content 1.1 Required"
    And I press "Add web/embed link"
    And I set the following fields to these values:
      | My Module   | 1                                            |
      | Title       | Test My Activities View 3                    |
      | Description | Test My Activities View 3 Add web/embed link |
      | Web link    | https://www.youtube.com/watch?v=ktAnpf_nu5c  |
    And I press "Save"
    And I follow "My Content > My Activities" in the openstudio navigation
    And I should see "Test My Activities View 3"
    And I should see "Content 1.1 Required"
    Then the "src" attribute of "img.openstudio-grid-item-thumbnail" "css_element" should contain "online_rgb_32px"

  @_file_upload
  Scenario:  Edit the setting of the new content uploaded
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "a.openstudio-profile-mypaticipation" "css_element"
    And I follow "Content 1.1 Required"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                          |
      | Title       | Test My Activities View 4                  |
      | Description | My Activities Description 4                |
      | Files       | mod/openstudio/tests/importfiles/test2.jpg |
    And I set the field "Describe this image for someone who cannot see it" to "This is image alt"
    And I press "Save"
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "div.openstudio-grid-item-content-preview" "css_element"
    And I go to content edit view
    And I set the following fields to these values:
      | My Module   | 1                           |
      | Title       | Test My Activities View 5   |
      | Description | My Activities Description 5 |
    And I press "Save"
    And I follow "My Content > My Activities" in the openstudio navigation
    And I should see "Test My Activities View 5"
    And I should not see "Test My Activities View 4"
    And I should see "Content 1.1 Required"
    Then the "src" attribute of "img.openstudio-grid-item-thumbnail" "css_element" should contain "test2.jpg"

  Scenario: Don't show upload buttons when no additional content is allowed.
    When I set the following fields to these values:
      | Content Name | Folder content 3.2 |
      | Is folder?   | 1                  |
    And I press "Save Changes"
    And I follow "Folder content 3.2"
    And I set the following fields to these values:
      | Number of additional contents allowed | 0 |
    And I press "Save Changes"
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "a.openstudio-profile-mypaticipation" "css_element"
    And I follow "Folder content 3.2"
    # Check add section is hidden.
    Then I should not see "Add new content"
    And I should not see "Upload content to folder"
    And I should not see "Select existing post to add to folder"

  Scenario: Check order of blocks defined in manage levels.
    # Check if there is only 1 activity block then selected it by default.
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "My Content > My Activities" in the openstudio navigation
    Then I should see "Activity 1" in the "//div[contains(@class, 'openstudio-activity-title-wrapper')][1]/h2" "xpath_element"
    And "//div[contains(@class, 'openstudio-grid')][1]//div[contains(@class, 'openstudio-grid-item')][1]//a[contains(., 'Content 1.1 Required')]" "xpath_element" should exist
    And I should see "Activity 3" in the "//div[contains(@class, 'openstudio-activity-title-wrapper')][2]/h2" "xpath_element"
    # Add new content to Activity 1.
    And I navigate to "Manage levels" in current page administration
    And I follow "Block 1"
    And I follow "Activity 1"
    And I press "Add another Content"
    And I set the field "Content Name" to "F-Content 1.2"
    And I press "Add another Content"
    And I set the field "Content Name" to "A-Content 1.3"
    And I press "Save Changes"
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "My Content > My Activities" in the openstudio navigation
    Then I should see "Activity 1" in the "//div[contains(@class, 'openstudio-activity-title-wrapper')][1]/h2" "xpath_element"
    # Check order displayed should be the same as the order set in Manage Levels.
    And "//div[contains(@class, 'openstudio-grid')][1]//div[contains(@class, 'openstudio-grid-item')][1]//a[contains(., 'Content 1.1 Required')]" "xpath_element" should exist
    And "//div[contains(@class, 'openstudio-grid')][1]//div[contains(@class, 'openstudio-grid-item')][2]//a[contains(., 'F-Content 1.2')]" "xpath_element" should exist
    And "//div[contains(@class, 'openstudio-grid')][1]//div[contains(@class, 'openstudio-grid-item')][3]//a[contains(., 'A-Content 1.3')]" "xpath_element" should exist
    And I should see "Activity 3" in the "//div[contains(@class, 'openstudio-activity-title-wrapper')][2]/h2" "xpath_element"
    And "//div[contains(@class, 'openstudio-grid')][2]//div[contains(@class, 'openstudio-grid-item')][1]//a[contains(., 'Content 3.1')]" "xpath_element" should exist
    # Add new Block 2.
    And I navigate to "Manage levels" in current page administration
    And I press "Add another Block"
    And I set the field "Block Name" to "Block 2"
    And I press "Save Changes"
    Then I should see "Block 2"
    # Add new Activity 1 to Block 2.
    When I follow "Block 2"
    And I press "Add another Activity"
    And I set the field "Activity Name" to "Activity 1 - Block 2"
    And I press "Add another Activity"
    Then I should see "Activity 1 - Block 2"
    # Add new content to Activity 1 - Block 2.
    When I follow "Activity 1 - Block 2"
    And I press "Add another Content"
    And I set the field "Content Name" to "Content 1.1 - Block 2"
    And I press "Save Changes"
    Then I should see "Content 1.1 - Block 2"
    # Move Block 2 up on Block 1 and check the order of appearance.
    When I follow "Manage levels"
    And I click on "//div[contains(@class, 'fcontainer')]//div[position()=1]//div[contains(@class, 'felement')]//input[contains(@title, 'Move Down')]" "xpath_element"
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "My Content > My Activities" in the openstudio navigation
    Then I should see "Activity 1 - Block 2" in the "//div[contains(@class, 'openstudio-activity-title-wrapper')][1]/h2" "xpath_element"
    And "//div[contains(@class, 'openstudio-grid')][1]//div[contains(@class, 'openstudio-grid-item')][1]//a[contains(., 'Content 1.1 - Block 2')]" "xpath_element" should exist
    And I should see "Activity 1" in the "//div[contains(@class, 'openstudio-activity-title-wrapper')][2]/h2" "xpath_element"
    And "//div[contains(@class, 'openstudio-grid')][2]//div[contains(@class, 'openstudio-grid-item')][1]//a[contains(., 'Content 1.1 Required')]" "xpath_element" should exist
    And "//div[contains(@class, 'openstudio-grid')][2]//div[contains(@class, 'openstudio-grid-item')][2]//a[contains(., 'F-Content 1.2')]" "xpath_element" should exist
    And "//div[contains(@class, 'openstudio-grid')][2]//div[contains(@class, 'openstudio-grid-item')][3]//a[contains(., 'A-Content 1.3')]" "xpath_element" should exist
    And I should see "Activity 3" in the "//div[contains(@class, 'openstudio-activity-title-wrapper')][3]/h2" "xpath_element"
    And "//div[contains(@class, 'openstudio-grid')][3]//div[contains(@class, 'openstudio-grid-item')][1]//a[contains(., 'Content 3.1')]" "xpath_element" should exist

  Scenario: Set "Enable pinboard" is 0 and check order of blocks defined in manage levels.
    When I am on the "Course 1" "Course" page
    And I open "Test Open Studio name 1" actions menu
    And I choose "Edit settings" in the open action menu
    And I follow "Custom features"
    And I set the field "pinboard" to "0"
    And I press "Save and display"
    Then I should see "Activity 1" in the "//div[contains(@class, 'openstudio-activity-title-wrapper')][1]/h2" "xpath_element"
    And "//div[contains(@class, 'openstudio-grid')][1]//div[contains(@class, 'openstudio-grid-item')][1]//a[contains(., 'Content 1.1 Required')]" "xpath_element" should exist
    And I should see "Activity 3" in the "//div[contains(@class, 'openstudio-activity-title-wrapper')][2]/h2" "xpath_element"

  Scenario: Show expand collapse.
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "My Content > My Activities" in the openstudio navigation
    And I should see "Activity 1"
    And I should see "Activity 3"
    Then "#openstudio-collapseall" "css_element" should be visible
    And "#openstudio-expandall" "css_element" should not be visible
    And ".openstudio-collapse > [title='Collapse Activity 1']" "css_element" should be visible
    And ".openstudio-expand > [title='Expand Activity 1']" "css_element" should not be visible
    And ".openstudio-collapse > [title='Collapse Activity 3']" "css_element" should be visible
    And ".openstudio-expand > [title='Expand Activity 3']" "css_element" should not be visible
    And I click on "#openstudio-collapseall" "css_element"
    And I should see "Activity 1"
    And ".openstudio-expand > [title='Expand Activity 1']" "css_element" should be visible
    And I should not see "Content 1.1 Required"
    And I should see "Activity 3"
    And ".openstudio-expand > [title='Expand Activity 3']" "css_element" should be visible
    And I should not see "Content 3.1"
    And I click on ".openstudio-expand > [title='Expand Activity 1']" "css_element"
    And I should see "Content 1.1 Required"
    And I should not see "Content 3.1"
    And I click on ".openstudio-expand > [title='Expand Activity 3']" "css_element"
    And I should see "Content 1.1 Required"
    And I click on ".openstudio-collapse > [title='Collapse Activity 1']" "css_element"
    And I should see "Content 3.1"
    # Store expand status after clicking and reload page.
    And I reload the page
    And I wait until the page is ready
    And I should not see "Content 1.1 Required"
    And I should see "Content 3.1"

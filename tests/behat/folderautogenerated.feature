@ou @ou_vle @mod @mod_openstudio @mod_openstudio_test_folder @_file_upload @javascript
Feature: Auto generated Open Studio Folder
When using Open Studio with other users
As a teacher
I need to create a folder and it can be auto-generated.

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

  Scenario: Add new folder using sharing level Module only.
    When I am on the "Course 1" "course" page logged in as teacher1
    And I turn editing mode on
    And I add a openstudio activity to course "Course 1" section "1" and I fill the form with:
      | Name               | Test Open Studio name 1      |
      | Description        | Test Open Studio description |
      | ID number          | OS1                          |
      | Group mode         | Separate groups              |
      | Grouping           | grouping1                    |
      | Enable folders     | 1                            |
      | Enable 'My Module' | 1                            |
      # Module only.
      | Sharing level      | 3                            |
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
    And I follow "Activity 1"
    And I press "Add another Content"
    And I set the field "Content Name" to "Folder 1.1 Required"
    And I set the field "Required (TMA)" to "1"
    And I set the field "Is folder?" to "1"
    And I press "Save Changes"
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "My Content > My Activities" in the openstudio navigation
    And I should see "Folder 1.1 Required"
    # View the folder detail so it will create an auto-generated content.
    And I click on "//a[contains(@data-name,'Folder 1.1 Required')]" "xpath_element"
    # Go to specific pages to see if there is any empty folder content (auto-generated folder).
    # View on My Module.
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "Shared Content > My Module" in the openstudio navigation
    Then "//div[contains(@class, 'openstudio-grid-item-content')]" "xpath_element" should not exist
    # View on My Group.
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "Shared Content > My Group" in the openstudio navigation
    And "//div[contains(@class, 'openstudio-grid-item-content')]" "xpath_element" should not exist
    # View on My Pinboard.
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "My Content > My Pinboard" in the openstudio navigation
    And "//div[contains(@class, 'openstudio-grid-item-content')]" "xpath_element" should not exist
    # Go back to folder detail and edit it.
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "//a[contains(@data-name,'Folder 1.1 Required')]" "xpath_element"
    And I follow "Edit folder details and sharing"
    And I press "Save"
    # View on My Module.
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "Shared Content > My Module" in the openstudio navigation
    And I should see "Folder 1.1 Required"
    # View on My Group.
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "Shared Content > My Group" in the openstudio navigation
    And I should see "Folder 1.1 Required"
    # View on My Pinboard.
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "My Content > My Pinboard" in the openstudio navigation
    # Because this folder used module visibility.
    And I should not see "Folder 1.1 Required"

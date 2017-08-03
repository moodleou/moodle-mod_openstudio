@ou @ou_vle @mod @mod_openstudio @mod_openstudio_manage_folders
Feature: Manage open studio folders
  In order to provide pre-configured set activities
  As a teacher
  I need to be able to pre-configre set templates in a studio


  Background: Setup course and studio
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
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
      | user     | group  |
      | teacher1 | G1 |
    And I log in as "teacher1"
    And I am on site homepage
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "OpenStudio 2 (pilot only)" to section "1" and I fill the form with:
      | Name | Test Open Studio name 1 |
      | Description | Test Open Studio description |
      | Group mode | Visible groups |
      | Grouping | grouping1 |
      | Enable pinboard | 99 |
      | Enable Folders  | 1 |
      | Abuse reports are emailed to | teacher1@asd.com |
      | ID number                    | OS1              |
    And all users have accepted the plagarism statement for "OS1" openstudio

  @javascript
  Scenario: Manage folder templates
    Given I follow "Test Open Studio name 1"
    And I follow "Administration > Manage levels" in the openstudio navigation
    And I press "Add another Block"
    And I set the field "Block Name" to "Block 1"
    And I press "Save Changes"
    And I follow "Block 1"
    And I press "Add another Activity"
    And I set the field "Activity Name" to "Activity 1"
    And I press "Save Changes"
    And I follow "Activity 1"

    When I press "Add another Content"
    Then "Content Name" "field" should exist
    And "Position" "field" should exist
    And "Required (TMA)" "checkbox" should exist
    And "Is folder?" "checkbox" should exist
    And "Is collection?" "checkbox" should not exist

    When I set the field "Content Name" to "Content 1"
    And I press "Add another Content"
    When I set the field "odsnewcontentname[0]" to "Content 2"
    When I set the field "Is folder?" to "1"
    And I press "Save Changes"
    Then I should see "Content 1"
    And  I should see "Content 2 (Folder)"
    And "Content 1" "link" should not exist
    And "Content 2" "link" should exist

    When I click on "//div[contains(., 'Content 2')]/input[@title='Edit Name']" "xpath_element"
    And I set the field "Is folder?" to "0"
    And I press "Save Changes"
    Then I should see "Content 2"
    And "Content 2" "link" should not exist
    When I click on "//div[contains(., 'Content 2')]/input[@title='Edit Name']" "xpath_element"
    And I set the field "Is folder?" to "1"
    And I press "Save Changes"
    Then I should see "Content 2 (Folder)"
    And "Content 2" "link" should exist

    When I follow "Content 2"
    Then I should see "Configure folder - Content 2"
    And "Folder guidance text" "field" should exist
    And "Number of additional contents allowed" "field" should exist

    When I set the following fields to these values:
      | Folder guidance text                     | Lorem ipsum dolor sit amet |
      | Number of additional contents allowed    | 2                          |
    And I press "Save Changes"
    Then the following fields match these values:
      | Folder guidance text                     | Lorem ipsum dolor sit amet |
      | Number of additional contents allowed    | 2                          |

    When I press "Add another Content"
    Then I should see "Content 1" in the "div.col-md-9.form-inline.felement > div.form-control-static > h3" "css_element"
    And "Name" "field" should exist
    And "Folder guidance text" "field" should exist
    And "Prevent re-ordering?" "field" should exist

    When I set the following fields to these values:
      | Name                  | Set Content 1                  |
      | Folder guidance text  | Nunc sagittis sit amet mauris. |
      | Prevent re-ordering?  | 1                              |
    And I press "Save Changes"
    Then the following fields match these values:
      | Name                  | Set Content 1                  |
      | Folder guidance text  | Nunc sagittis sit amet mauris. |
      | Prevent re-ordering?  | 1                              |

    When I press "Add another Content"
    And I set the field "id_contentname_1" to "Set Content 2"
    And I press "Add another Content"
    And I set the field "id_contentname_2" to "Set Content 3"
    And I press "Save Changes"
    Then the following fields match these values:
      | id_contentname_0  | Set Content 1 |
      | id_contentname_1  | Set Content 2 |
      | id_contentname_2  | Set Content 3 |
    And "id_contentdelete_0" "button" should be visible
    And "id_contentmovedown_0" "button" should be visible
    And "id_contentmoveup_0" "button" should not be visible
    And "id_contentdelete_1" "button" should be visible
    And "id_contentmovedown_1" "button" should be visible
    And "id_contentmoveup_1" "button" should be visible
    And "id_contentdelete_2" "button" should be visible
    And "id_contentmovedown_2" "button" should not be visible
    And "id_contentmoveup_2" "button" should be visible

    When I press "Add another Content"
    Then I should see "Content 4"
    And "id_contentdelete_3" "button" should not be visible
    And "id_contentmovedown_3" "button" should not be visible
    And "id_contentmoveup_3" "button" should not be visible

    Given I press "Cancel"
    When I click on "id_contentmovedown_0" "button"
    Then the field "id_contentname_0" matches value "Set Content 2"
    And the field "id_contentname_1" matches value "Set Content 1"

    When I click on "id_contentmoveup_2" "button"
    Then the field "id_contentname_1" matches value "Set Content 3"
    And the field "id_contentname_2" matches value "Set Content 1"

    When I click on "id_contentdelete_1" "button"
    Then the field "id_contentname_1" matches value "Set Content 1"
    And I should not see "Set Content 3"
    And "id_contentname_2" "field" should not exist

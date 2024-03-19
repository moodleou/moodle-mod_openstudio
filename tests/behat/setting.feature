@ou @ou_vle @mod @mod_openstudio @mod_openstudio_test_content @_file_upload @javascript
Feature: Edit Open Studio settings

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
      | student2 | G1    |
      | teacher1 | G2    |
      | student2 | G2    |
      | student3 | G2    |
      | teacher1 | G3    |
      | student3 | G3    |
      | student4 | G3    |
    And I am logged in as "teacher1"

  Scenario: Check default settings
    When I am on the "Course 1" "Course" page
    And I turn editing mode on
    And I add a "OpenStudio 2" to section "1"
    And I click on "Expand all" "link" in the "region-main" "region"
    Then I should see "Your word for 'My Module'"
    And I should see "Your word for 'My Group'"
    And I should see "Your word for 'My Activities'"
    And I should see "Your word for 'My Pinboard'"
    And I should see " Enable 'My Module'"
    And the "value" attribute of "input#id_pinboard" "css_element" should contain "100"
    And I should see "Site upload limit" in the "Maximum file size" "field"

  Scenario: Behavior handling for People Tab
    When I am on the "Course 1" "Course" page
    And I turn editing mode on
    And I add a "OpenStudio 2" to section "1" and I fill the form with:
      | Name                         | Test Open Studio name 1      |
      | Description                  | Test Open Studio description |
      | Enable 'My Module'           | 0                            |
      | Sharing level                | 1,7,2                        |
      | Abuse reports are emailed to | teacher1@asd.com             |
      | ID number                    | OS1                          |
      | id_tutorrolesgroup_1         | 1                            |

    And I add a "OpenStudio 2" to section "1" and I fill the form with:
      | Name                          | Test Open Studio name 2        |
      | Description                   | Test Open Studio description 2 |
      | Your word for 'My Module'     | My Module                      |
      | Your word for 'My Group'      | My Group                       |
      | Your word for 'My Activities' | My Activities                  |
      | Your word for 'My Pinboard'   | My Pinboard                    |
      | Group mode                    | Visible groups                 |
      | Grouping                      | grouping1                      |
      | Enable pinboard               | 99                             |
      | Enable folders                | 1                              |
      | Abuse reports are emailed to  | teacher1@asd.com               |
      | ID number                     | OS2                            |
      | id_tutorrolesgroup_1          | 1                              |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And all users have accepted the plagarism statement for "OS2" openstudio
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    Then I should not see "People"
    And I am on the "Course 1" "Course" page
    And I am on the "Test Open Studio name 2" "openstudio activity" page
    Then I should see "People"

  Scenario: Behavior handling for Shared Content
    Given I am on the "Course 1" "Course" page
    And I turn editing mode on

    And I add a "OpenStudio 2" to section "1" and I fill the form with:
      | Name                          | Test Open Studio name 1        |
      | Description                   | Test Open Studio description 1 |
      | Your word for 'My Module'     | My Module                      |
      | Your word for 'My Activities' | My Activities                  |
      | Your word for 'My Pinboard'   | My Pinboard                    |
      | Enable 'My Module'            | 0                              |
      | Sharing level                 | 1,7,2                          |
      | Group mode                    | Visible groups                 |
      | Grouping                      | grouping1                      |
      | Enable pinboard               | 99                             |
      | Enable folders                | 1                              |
      | Abuse reports are emailed to  | teacher1@asd.com               |
      | ID number                     | OS1                            |
      | id_tutorrolesgroup_1          | 1                              |

    And I add a "OpenStudio 2" to section "1" and I fill the form with:
      | Name                         | Test Open Studio name 2      |
      | Description                  | Test Open Studio description |
      | Abuse reports are emailed to | teacher1@asd.com             |
      | ID number                    | OS2                          |
      | id_tutorrolesgroup_1         | 1                            |

    And I add a "OpenStudio 2" to section "1" and I fill the form with:
      | Name                          | Test Open Studio name 3        |
      | Description                   | Test Open Studio description 3 |
      | Your word for 'My Module'     | My Module                      |
      | Your word for 'My Group'      | My Group                       |
      | Your word for 'My Activities' | My Activities                  |
      | Your word for 'My Pinboard'   | My Pinboard                    |
      | Group mode                    | Visible groups                 |
      | Grouping                      | grouping1                      |
      | Enable pinboard               | 99                             |
      | Enable folders                | 1                              |
      | Abuse reports are emailed to  | teacher1@asd.com               |
      | ID number                     | OS3                            |
      | id_tutorrolesgroup_1          | 1                              |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And all users have accepted the plagarism statement for "OS2" openstudio
    And all users have accepted the plagarism statement for "OS3" openstudio

    # Only My Group is available
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    Then I should see "Shared Content"
    And I follow "People" in the openstudio navigation
    And I follow "Shared Content"
    Then I should see "My Group"

    # Only My Module is available
    When I am on the "Test Open Studio name 2" "openstudio activity" page
    Then I should see "Shared Content"
    And I follow "People" in the openstudio navigation
    And I follow "Shared Content"
    Then I should see "My Module"

    # My Module and My Group are available
    When I am on the "Test Open Studio name 3" "openstudio activity" page
    Then I should see "Shared Content"
    And I follow "Shared content" in the openstudio navigation
    Then I should see "My Group"
    And I should see "My Module"

  Scenario: Behavior handling for My Content with My Activities:
    When I am on the "Course 1" "Course" page
    And I turn editing mode on
    And I wait until the page is ready
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
    And I set the field "Content Name" to "Content 1"
    And I press "Add another Content"
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "My content" in the openstudio navigation
    Then I should see "My Pinboard"
    And I should see "My Activities"

  Scenario: Behavior handling for My Content without My Activities:
    When I am on the "Course 1" "Course" page
    And I turn editing mode on
    And I wait until the page is ready
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
    And all users have accepted the plagarism statement for "OS1" openstudio
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "My Content"
    Then I should see "My Pinboard"

  Scenario: Behavior handling drop down label to make consistency to Setting:
    When I am on the "Course 1" "Course" page
    And I turn editing mode on
    And I wait until the page is ready
    And I add a "OpenStudio 2" to section "1" and I fill the form with:
      | Name                          | Test Open Studio name 1      |
      | Description                   | Test Open Studio description |
      | Your word for 'My Module'     | Module 1                     |
      | Your word for 'My Group'      | Group 1                      |
      | Your word for 'My Activities' | Activities 1                 |
      | Your word for 'My Pinboard'   | Pinboard  1                  |
      | Group mode                    | Visible groups               |
      | Grouping                      | grouping1                    |
      | Enable pinboard               | 99                           |
      | Enable folders                | 1                            |
      | Abuse reports are emailed to  | teacher1@asd.com             |
      | ID number                     | OS1                          |
      | id_tutorrolesgroup_1          | 1                            |

    And the following open studio "level1s" exist:
      | openstudio | name | sortorder |
      | OS1        | B1   | 1         |
    And the following open studio "level2s" exist:
      | level1 | name | sortorder |
      | B1     | A1   | 1         |
    And the following open studio "level3s" exist:
      | level2 | name | sortorder |
      | A1     | S1   | 1         |
    And the following open studio "folder templates" exist:
      | level3 | additionalcontents |
      | S1     | 2                  |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And I am on the "Test Open Studio name 1" "openstudio activity" page

    # check drop down label apply Setting
    And I follow "Shared Content"
    And I should see "Module 1"
    And I should see "Group 1"
    And I follow "Shared Content"
    And I follow "My Content"
    And I should see "Activities 1"
    And I should see "Pinboard  1"

    # remove Your word for 'My Module' in Setting
    And I follow "My Content"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Your word for 'My Module' |  |
    And I press "Save and display"
    And I follow "Shared Content"
    And I should not see "Module 1"
    And I should see "My Module"

    And I follow "Group 1"
    And I should see "Group 1"
    And I follow "Shared content > My Module" in the openstudio navigation
    And I should see "My Module"
    And I follow "Shared content > Group 1" in the openstudio navigation
    And I should see "Group 1"
    And I follow "My Content > Pinboard 1" in the openstudio navigation
    And I should see "Pinboard  1"
    And I follow "My Content > Activities 1" in the openstudio navigation
    And I should see "Activities 1"

  Scenario: Behavior handling upload icon when pinboard disabled:
    When I am on the "Course 1" "Course" page
    And I turn editing mode on
    And I add a "OpenStudio 2" to section "1" and I fill the form with:
      | Name                         | Test Open Studio name 1      |
      | Description                  | Test Open Studio description |
      | Group mode                   | Visible groups               |
      | Grouping                     | grouping1                    |
      | Enable pinboard              | 0                            |
      | Enable folders               | 1                            |
      | Abuse reports are emailed to | teacher1@asd.com             |
      | ID number                    | OS1                          |
      | id_tutorrolesgroup_1         | 1                            |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I should see "My Module"
    And I should not see "My Content"
    And I should not see "Add new content"
    And I follow "Shared content > My Group" in the openstudio navigation
    And I should not see "Add new content"

  Scenario: Behavior handling content view when pinboard disabled:
    When I am on the "Course 1" "Course" page
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
    And all users have accepted the plagarism statement for "OS1" openstudio
    And the following open studio "contents" exist:
      | openstudio | user     | name           | description                | visibility |
      | OS1        | student1 | Test content A | Test content 1 description | module     |
      | OS1        | student1 | Test content B | Test content 2 description | module     |
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I navigate to "Settings" in current page administration
    And I click on "Expand all" "link" in the "region-main" "region"
    # disabled pinboard
    And I set the field "Enable pinboard" to "0"
    And I press "Save and display"
    And I should see "Test content A"
    And I should see "Test content B"
    And I should not see "Add new content"
    And I should not see "My Content"

  Scenario: Behavior handling mandatory fields
    When I am on the "Course 1" "Course" page
    And I turn editing mode on
    And I add a "OpenStudio 2" to section "1" and I fill the form with:
      | Name                         | Test Open Studio name 1      |
      | Description                  | Test Open Studio description |
      | Enable 'My Module'           | 0                            |
      | Sharing level                | 1,7,2                        |
      | Abuse reports are emailed to | teacher1@asd.com             |
      | ID number                    | OS1                          |
      | id_tutorrolesgroup_1         | 1                            |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I navigate to "Settings" in current page administration
    And I click on "Expand all" "link" in the "region-main" "region"
    And I set the following fields to these values:
      | Enable 'My Module'   | 0       |
      | Sharing level        | 1,7,2,3 |
      | id_tutorrolesgroup_1 | 0       |
    And I press "Save and display"
    And I should see "Enable 'My Module' must be chosen when option 'Module - visible all module members' in Sharing level is selected"
    And I should see "You must select one or more tutor roles when visible to tutors is selected"
    And I set the following fields to these values:
      | Enable 'My Module'   | 1 |
      | id_tutorrolesgroup_1 | 1 |
    And I press "Save and display"
    Then I should see "Shared Content"
    And I follow "Add new content"
    And I set the following fields to these values:
      | My Module | 1 |
    Then I should see "My Module"

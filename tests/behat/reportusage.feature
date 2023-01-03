@ou @ou_vle @mod @mod_openstudio @mod_openstudio_report_usage @javascript
Feature: My Activity view in Open Studio
When using Open Studio with other users
As a teacher
I view report usage

  Background:
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
    And I am on the "Course 1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I add a "OpenStudio 2" to section "1" and I fill the form with:
      | Name                         | Test Open Studio name 1      |
      | Description                  | Test Open Studio description |
      | Enable pinboard              | 99                           |
      | Enable folders               | 1                            |
      | Abuse reports are emailed to | teacher1@asd.com             |
      | ID number                    | OS1                          |
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
    And I set the field "Content Name" to "Content 1.1 Required"
    And I press "Add another Content"
    And I set the field "Content Name" to "Content 1.2 Required"
    And I press "Add another Content"
    And I set the field "Content Name" to "Content 1.3 Required"
    And I press "Add another Content"
    And I am on the "Test Open Studio name 1" "openstudio activity" page

  @_file_upload
  Scenario: Creating dummy data for report
    When I follow "My Content > My Activities" in the openstudio navigation
    And I click on "a.openstudio-profile-mypaticipation" "css_element"
    And I follow "Content 1.1 Required"
    And I set the following fields to these values:
      | My Module   | 1                           |
      | Title       | Test My Activities View 1   |
      | Description | My Activities Description 1 |
    And I press "Save"
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "a.openstudio-profile-mypaticipation" "css_element"
    And I follow "Content 1.2 Required"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                          |
      | Title       | Test My Activities View 2                  |
      | Description | My Activities Description 2                |
      | Files       | mod/openstudio/tests/importfiles/test1.jpg |
    And I press "Save"
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "a.openstudio-profile-mypaticipation" "css_element"
    And I follow "Content 1.3 Required"
    And I press "Add web/embed link"
    And I set the following fields to these values:
      | My Module   | 1                                            |
      | Title       | Test My Activities View 3                    |
      | Description | Test My Activities View 3 Add web/embed link |
      | Web link    | https://www.youtube.com/watch?v=ktAnpf_nu5c  |
    And I press "Save"
    And I navigate to "Report usage" in current page administration
    And I should see "1" in the "//table[1]/tbody/tr[2]/td[6]" "xpath_element"
    And I should see "1" in the "//table[2]/tbody/tr[2]/td[5]" "xpath_element"
    And I should see "1" in the "//table[3]/tbody/tr[2]/td[6]" "xpath_element"

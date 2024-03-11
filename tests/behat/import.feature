@ou @ou_vle @mod @mod_openstudio @_file_upload @javascript
Feature: Import zip file to Open Studio
When using Open Studio
As a teacher
I need to import contents by zip file

  Background: Setup course and studio
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Student   | 1        | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category | format | numsections |
      | Course 1 | C1        | 0        | topics | 0           |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |

    And I am on the "Course 1" "Course" page logged in as "admin"
    And I turn editing mode on
    And I add a "OpenStudio 2" to section "0" and I fill the form with:
      | Name                 | Test Open Studio name 1      |
      | Description          | Test Open Studio description |
      | ID number            | OS1                          |
      | id_tutorrolesgroup_1 | 1                            |
    And all users have accepted the plagarism statement for "OS1" openstudio

  Scenario: Import zip file
    Given I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "student1"
    And I follow "My Content"
    And I follow "Import"
    And I upload "mod/openstudio/tests/importfiles/importtest.zip" file to "Choose zip file" filemanager
    And I press "Import"
    Then I should see "test.odt"
    Then I should see "test.pdf"
    Then I should see "test.pptx"
    Then I should see "test.ods"
    Then I should see "test.txt"
    Then I should see "test.m4v"
    Then I should see "test.m4a"
    Then I should see "test.webm"
    And I follow "test.ods"
    Then following "Download file attachment" should download between "2000" and "3000" bytes

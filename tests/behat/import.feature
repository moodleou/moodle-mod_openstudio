@ou @ou_vle @mod @mod_openstudio @_file_upload @javascript
Feature: Import zip file
    When using Open Studio
    As a teacher
    I need to import contents by zip file

    Background: Setup course and studio
        Given the following "users" exist:
            | username | firstname | lastname | email            |
            | student1 | Student   | 1        | student1@asd.com |
        And the following "courses" exist:
            | fullname | shortname | category | format | numsections |
            | Course 1 | C1        |        0 | topics | 0           |
        And the following "course enrolments" exist:
            | user     | course | role    |
            | student1 | C1     | student |

        And I log in as "admin"
        And I am on "Course 1" course homepage
        And I turn editing mode on
        And I add a "OpenStudio 2 (pilot only)" to section "0" and I fill the form with:
            | Name        | Test Open Studio name 1      |
            | Description | Test Open Studio description |
            | ID number   | OS1                          |
        And all users have accepted the plagarism statement for "OS1" openstudio
        And I log out

    Scenario: Import zip file
        Given I log in as "student1"
        And I am on "Course 1" course homepage
        And I follow "Test Open Studio name 1"
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
        And I follow "test.ods"
        Then following "Download file attachment" should download between "2000" and "3000" bytes

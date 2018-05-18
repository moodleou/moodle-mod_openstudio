@ou @ou_vle @mod @mod_openstudio @_file_upload
Feature: Add/Reply/Flag/Delete comment
    In order to Add/Reply/Flag/Delete comment
    As a student
    I need to be able to Add/Reply/Flag/Delete comment

    Background: Setup course and studio
        Given the following "users" exist:
            | username | firstname | lastname | email            |
            | student1 | Student   | 1        | student1@asd.com |
            | student2 | Student   | 2        | student2@asd.com |
        And the following "courses" exist:
            | fullname | shortname | category | format      | numsections |
            | Course 1 | C1        | 0        | oustudyplan | 0           |
        And the following "course enrolments" exist:
            | user     | course | role    |
            | student1 | C1     | student |
            | student2 | C1     | student |

        # Enable REST web service
        Then I log in as "admin"
        And the following config values are set as admin:
            | enablewebservices | 1 |
        And I navigate to "Plugins > Manage protocols" in site administration
        And I click on "Enable" "link" in the "REST protocol" "table_row"
        And I press "Save changes"

        And the following open studio "instances" exist:
            | course | name           | description                | pinboard | idnumber | tutorroles |
            | C1     | Sharing Studio | Sharing Studio description | 99       | OS1      | manager    |
        And the following open studio "contents" exist:
            | openstudio | user     | name            | description              | visibility |
            | OS1        | student1 | Student slot 1  | Test slot 1 description  | module     |

        And all users have accepted the plagarism statement for "OS1" openstudio
        And I log out

    @javascript
    Scenario: Add/Reply/Flag/Delete

        # Add new comment
        And I log in as "student1"
        And I am on "Course 1" course homepage
        And I follow "Sharing Studio"
        And I follow "Student slot 1"
        And I press "Add new comment"
        And I set the field "Comment" to "Comment text"
        And I upload "mod/openstudio/tests/importfiles/test.mp3" file to "Attach an audio (MP3 file) as comment" filemanager
        And I press "Post comment"
        Then I should see "Comment text"

        # Flag comment
        And I follow "Like comment"
        Then I should see "1" in the ".openstudio-comment-flag-count" "css_element"
        Then I should not see "Like comment"

        # Reply comment
        And I press "Reply"
        And I set the field "Comment" to "Comment text reply"
        And I press "Post comment"
        Then I should see "Comment text reply"

        # Reply other user's comment
        And I am on site homepage
        And I log out
        And I log in as "student2"
        And I am on "Course 1" course homepage
        And I follow "Sharing Studio"
        And I follow "Student slot 1"
        Then I should see "Report comment"
        Then I should not see "Delete comment"

        And I press "Reply"
        And I set the field "Comment" to "Comment text reply 2"
        And I press "Post comment"
        Then I should see "Comment text reply"

        # Delete comment
        And I follow "Delete comment"
        And I click on "Delete" "button" in the "Delete comment" "dialogue"
        Then I should not see "Comment text reply 2"
    
    @javascript
    Scenario: Comment Report Abuse

        # The OU Alert Plugin is enable by Admin
        When I log in as "admin"
        And I navigate to "OU Alerts" node in "Site administration > Plugins > Reports"
        And I follow "OU Alerts"
        And I set the following fields to these values:
            | id_s__oualerts_enabled | 1 |
        And I press "Save changes"
        And I should see "Changes saved"

        # Add new comment
        And I log out
        And I log in as "student1"
        And I am on "Course 1" course homepage
        And I follow "Sharing Studio"
        And I follow "Student slot 1"
        And I press "Add new comment"
        And I set the field "Comment" to "Comment text"
        And I upload "mod/openstudio/tests/importfiles/test.mp3" file to "Attach an audio (MP3 file) as comment" filemanager
        And I press "Post comment"

        # switch to student2
        And I am on site homepage
        And I log out
        And I log in as "student2"
        And I am on "Course 1" course homepage
        And I follow "Sharing Studio"
        And I follow "Student slot 1"
        And I should see "Report comment"
        And I follow "Report comment"
        And I set the following fields to these values:
            | id_oualerts_reasons_1 | 1                               |
            | id_oualerts_reasons_8 | 1                               |
            | oualerts_message      | Report Open Studio Comment Test |
        And I press "Send alert"
        And I should see "Student slot 1"
        And I follow "Report comment"
        Then I should see "Message: Report Open Studio Comment Test"

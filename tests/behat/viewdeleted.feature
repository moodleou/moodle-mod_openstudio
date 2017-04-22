@ou @ou_vle @mod @mod_openstudio @mod_openstudio_folder_overview @javascript
Feature: View deleted posts
    When using Open Studio with manager
    As a manager
    I need to view/restore deleted posts and restore

    Background: Setup course and studio
        Given the following "users" exist:
            | username | firstname | lastname | email            |
            | manager1 | Manager   | 1        | manager1@asd.com |
        And the following "courses" exist:
            | fullname | shortname | category |
            | Course 1 | C1        | 0        |
        And the following "course enrolments" exist:
            | user     | course | role    |
            | manager1 | C1     | manager |
        And the following open studio "instances" exist:
            | course | name           | description                | pinboard | idnumber | tutorroles |
            | C1     | Sharing Studio | Sharing Studio description | 99       | OS1      | manager    |
        And the following open studio "folders" exist:
            | openstudio | user     | name     | description          | visibility | contenttype    |
            | OS1        | manager1 | Folder 1 | Folder Description 1 | module     | folder_content |
        And the following open studio "contents" exist:
            | openstudio | user     | name      | description           | file                                       | visibility |
            | OS1        | manager1 | Content 1 | Content Description 1 | mod/openstudio/tests/importfiles/test1.jpg | module     |
        And the following open studio "folder contents" exist:
            | openstudio | user     | folder   | content   |
            | OS1        | manager1 | Folder 1 | Content 1 |
        And all users have accepted the plagarism statement for "OS1" openstudio

    Scenario: View/Restore deleted posts in Folder
        # Delete post
        Given I am on site homepage
        And I log in as "manager1"
        And I follow "Course 1"
        And I follow "Sharing Studio"
        And I follow "My Content"
        And I follow "Folder 1"
        And I follow "Content 1"
        And I press "Delete"
        Then I should not see "Content 1"

        # View deleted posts
        And I click on ".openstudio-delete-ok-btn" "css_element"
        And I press "View deleted"
        Then I should see "Content 1"

        # Restore deleted posts.
        And I press "Restore"
        And I reload the page
        Then I should see "Content 1"

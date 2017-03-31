@ou @ou_vle @mod @mod_openstudio @javascript
Feature: Search content
In order to search content
As a student
I need to be able to search within OpenStudio

    Background: Setup course and studio
        Given the following "users" exist:
            | username | firstname | lastname | email            |
            | student1 | Student   | 1        | student1@asd.com |
            | student2 | Student   | 2        | student2@asd.com |
        And the following "courses" exist:
            | fullname | shortname | category | format      |
            | Course 1 | C1        | 0        | oustudyplan |
        And the following "course enrolments" exist:
            | user     | course | role    |
            | student1 | C1     | student |
            | student2 | C1     | student |

        # Prepare a open studio
        And the following open studio "instances" exist:
            | course | name           | description                | pinboard | idnumber | tutorroles |
            | C1     | Sharing Studio | Sharing Studio description | 99       | OS1      | manager    |

          # Prepare open studio' contents and activities
        And the following open studio "contents" exist:
            | openstudio | user     | name              | description           | visibility |
            | OS1        | student1 | Student content 1 | Content Description 1 | private    |
            | OS1        | student1 | Student content 2 | Content Description 2 | private    |
            | OS1        | student1 | Student content 3 | Content Description 3 | module     |
            | OS1        | student1 | Student slot    4 | Slot Description 4    | module     |
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
        And the following open studio "folder content templates" exist:
            | level3 | name            |
            | S1     | folder_template |
        Given the following open studio "level3contents" exist:
            | openstudio | user     | name              | description           | weblink                                     | visibility | level3 | levelcontainer |
            | OS1        | student1 | Student content 5 | Content Description 5 | https://www.youtube.com/watch?v=ktAnpf_nu5c | module     | S1     | module         |
        And Open Studio levels are configured for "Sharing Studio"
        And all users have accepted the plagarism statement for "OS1" openstudio

    Scenario: Search my content
        # Using OSEP theme to display OSEP search form.
        Given I am using the OSEP theme
        When I log in as "student1" (in the OSEP theme)

        # Search my pinboard
        And I am on site homepage
        And I follow "Course 1"
        And I press "Expand all"
        And I follow "Sharing Studio"
        And I follow "My Content > My Pinboard" in the openstudio navigation
        And I set the field "query" to "content"
        And I press key "13" in the field "query"
        Then I should see "Student content 1"
        Then I should see "Student content 2"
        Then I should see "Student content 3"
        Then I should not see "Student slot 4"
        Then I should see "content — 3 results found"

        # Search my activity
        And I follow "My Content > My Activities" in the openstudio navigation
        And I set the field "query" to "content"
        And I press key "13" in the field "query"
        Then I should see "Student content 5"
        Then I should not see "Student content 1"
        Then I should not see "Student content 2"
        Then I should not see "Student content 3"
        Then I should not see "Student slot 4"
        Then I should see "content — 1 results found"

        # Search my module
        And I follow "Shared Content"
        And I set the field "query" to "content"
        And I press key "13" in the field "query"
        Then I should see "Student content 1"
        Then I should see "Student content 2"
        Then I should see "Student content 3"
        Then I should see "Student content 5"
        Then I should not see "Student slot 4"
        Then I should see "content — 4 results found"

        # Search my module by another student
        Given I am using the OSEP theme
        And I log out (in the OSEP theme)
        When I log in as "student2" (in the OSEP theme)
        And I follow "Course 1"
        And I press "Expand all"
        And I follow "Sharing Studio"
        And I set the field "query" to "content"
        And I press key "13" in the field "query"
        Then I should see "Student content 3"
        Then I should see "Student content 5"
        Then I should not see "Student content 1"
        Then I should not see "Student content 2"
        Then I should not see "Student slot 4"
        Then I should see "content — 2 results found"

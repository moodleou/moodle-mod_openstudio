@ou @ou_vle @mod @mod_openstudio
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
            | fullname | shortname | category | format      | numsections |
            | Course 1 | C1        | 0        | oustudyplan | 0           |
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
        # Use Legacy system for default.
        And the following config values are set as admin:
            | modulesitesearch | 2 | local_moodleglobalsearch |
            | activitysearch   | 1 | local_moodleglobalsearch |
        And Open Studio levels are configured for "Sharing Studio"
        And all users have accepted the plagarism statement for "OS1" openstudio

    Scenario: Search my content
        # Using OSEP theme to display OSEP search form.
        Given I am using the OSEP theme
        When I log in as "student1" (in the OSEP theme)

        # Search my pinboard
        And I am on "Course 1" course homepage
        And I follow "Sharing Studio"
        And I follow "My Pinboard"
        And I set the field "query" to "content"
        And I click on "form.oustudyplan-searchbox button" "css_element"
        Then I should see "Student content 1"
        Then I should see "Student content 2"
        Then I should see "Student content 3"
        Then I should not see "Student slot 4"
        Then I should see "content — 3 results found"

        # Search my activity
        And I follow "My Activities"
        And I set the field "query" to "content"
        And I click on "form.oustudyplan-searchbox button" "css_element"
        Then I should see "Student content 5"
        Then I should not see "Student content 1"
        Then I should not see "Student content 2"
        Then I should not see "Student content 3"
        Then I should not see "Student slot 4"
        Then I should see "content — 1 results found"

        # Search my module
        And I follow "Shared Content"
        And I set the field "query" to "content"
        And I click on "form.oustudyplan-searchbox button" "css_element"
        Then I should see "Student content 1"
        Then I should see "Student content 2"
        Then I should see "Student content 3"
        Then I should see "Student content 5"
        Then I should not see "Student slot 4"
        Then I should see "content — 4 results found"
        # Search my module - pagination.
        Given the following config values are set as admin:
          | streampagesize | 2 | openstudio |
        When I follow "Shared Content"
        And I set the field "query" to "content"
        And I click on "form.oustudyplan-searchbox button" "css_element"
        Then I should see "Student content 1"
        And I should see "Student content 2"
        And I should not see "Student content 3"
        And I should see "content — 2 or more results found"
        When I follow "More search results"
        Then I should see "Student content 3"
        And I should see "content — 2 or more results found"
        And the following config values are set as admin:
            | streampagesize | 100 | openstudio |

        # Search my module by another student
        Given I am using the OSEP theme
        And I log out (in the OSEP theme)
        When I log in as "student2" (in the OSEP theme)
        And I am on "Course 1" course homepage
        And I follow "Sharing Studio"
        And I set the field "query" to "content"
        And I click on "form.oustudyplan-searchbox button" "css_element"
        Then I should see "Student content 3"
        Then I should see "Student content 5"
        Then I should not see "Student content 1"
        Then I should not see "Student content 2"
        Then I should not see "Student slot 4"
        Then I should see "content — 2 results found"

        # Check searching with moodleglobalsearch enabled returns results from solr.
        Given the following config values are set as admin:
            | modulesitesearch | 2 | local_moodleglobalsearch |
            | activitysearch   | 2 | local_moodleglobalsearch |
        And global search expects the query "content" and will return:
            | nothing |
        And I am on "Course 1" course homepage
        And I follow "Sharing Studio"
        And I set the field "q" to "content"
        When I click on "form.oustudyplan-searchbox button" "css_element"
        Then I should not see "content — 2 results found"
        And I should see "Search result for terms:"
        And I should see "No results? Try searching from within My Module"

    Scenario: Search my folder
        # Using OSEP theme to display OSEP search form.
        Given I am using the OSEP theme
        When I log in as "student1" (in the OSEP theme)

         Given the following open studio "folders" exist:
        | openstudio | user     | name                         | description                       | visibility | contenttype    |
        | OS1        | student1 | Student content folder 1     | My Folder Overview Description 1  | private    | folder_content |
        | OS1        | student1 | Student content folder 2     | My Folder Overview Description 2  | module     | folder_content |

        # Search folder in my pinboard view
        And I am on "Course 1" course homepage
        And I follow "Sharing Studio"
        And I follow "My Pinboard"
        And I set the field "query" to "folder"
        And I click on "form.oustudyplan-searchbox button" "css_element"
        And I should see "folder — 2 results found"
        And I should see "Student content folder 1"
        And I should see "Student content folder 2"

        # Search folder in my module by another student
        And I log out (in the OSEP theme)
        And I log in as "student2" (in the OSEP theme)
        And I am on "Course 1" course homepage
        And I follow "Sharing Studio"
        And I set the field "query" to "folder"
        And I click on "form.oustudyplan-searchbox button" "css_element"
        And I should see "folder — 1 results found"
        And I should not see "Student content folder 1"
        And I should see "Student content folder 2"

        # Check searching with moodleglobalsearch enabled returns results from solr.
        Given the following config values are set as admin:
            | modulesitesearch | 2 | local_moodleglobalsearch |
            | activitysearch   | 2 | local_moodleglobalsearch |
        And global search expects the query "folder" and will return:
            | nothing |
        And I am on "Course 1" course homepage
        And I follow "Sharing Studio"
        And I set the field "q" to "folder"
        When I click on "form.oustudyplan-searchbox button" "css_element"
        Then I should not see "folder — 1 results found"
        And I should see "Search result for terms:"
        And I should see "No results? Try searching from within My Module"

    Scenario: Global search
        Given the following config values are set as admin:
            | modulesitesearch | 2 | local_moodleglobalsearch |
            | activitysearch   | 2 | local_moodleglobalsearch |

        When I log in as "student1"

        And the following open studio "folders" exist:
            | openstudio | user     | name                     | description                      | visibility | contenttype    | index | keyword |
            | OS1        | student1 | Student content folder 2 | My Folder Overview Description 2 | module     | folder_content | 1     | keyword |
            | OS1        | student1 | Student content folder 3 | My Folder Overview Description 3 | module     | folder_content | 1     | keyword |
        And the following open studio "contents" exist:
            | openstudio | user     | name         | description                    | visibility | index | keyword |
            | OS1        | student1 | My Content 1 | Test My Content Details View 1 | module     | 1     | keyword |
            | OS1        | student1 | My Content 2 | Test My Content Details View 2 | module     | 2     | keyword |
            | OS1        | student1 | My Content 3 | Test My Content Details View 3 | module     | 3     | keyword |
            | OS1        | student1 | My Content 4 | Test My Content Details View 4 | module     | 4     | keyword |
            | OS1        | student1 | My Content 5 | Test My Content Details View 5 | module     | 5     | keyword |
            | OS1        | student1 | My Content 6 | Test My Content Details View 6 | module     | 6     | keyword |
            | OS1        | student1 | My Content 7 | Test My Content Details View 7 | module     | 7     | keyword |
            | OS1        | student1 | My Content 8 | Test My Content Details View 8 | module     | 8     | keyword |
            | OS1        | student1 | My Content 9 | Test My Content Details View 9 | module     | 9     | keyword |
        And the following open studio "comments" exist:
            | openstudio | user     | content      | comment                   | index | keyword |
            | OS1        | student1 | My Content 1 | My Notification comment 1 | 1     | keyword |
            | OS1        | student1 | My Content 2 | My Notification comment 2 | 1     | keyword |

        And I am on "Course 1" course homepage
        Then I follow "Sharing Studio"
        And I set the field "q" to "keyword"
        And I click on "form.oustudyplan-searchbox button" "css_element"
        And I should see "keyword — 13 results found"
        And I should see "My Content 1"
        And I should not see "Previous search results"
        Given I follow "More search results"
        Then I should see "My Content 9"
        And I should not see "More search results"
        And I should see "keyword — 13 results found"
        Given I follow "Previous search results"
        Then I should not see "My Content 9"
        And I should not see "Previous search results"
        And I should see "More search results"
        And I should see "keyword — 13 results found"

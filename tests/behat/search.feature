@ou @ou_vle @mod @mod_openstudio @mod_openstudio_search
Feature: Open Studio search content
  In order to search content
  As a student
  I need to be able to search within OpenStudio

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
    And the following config values are set as admin:
      | enableglobalsearch | 1 |
    And Open Studio levels are configured for "Sharing Studio"
    And all users have accepted the plagarism statement for "OS1" openstudio

  @javascript
  Scenario: Search my content
    When I am logged in as "student1"

    # Check searching with moodleglobalsearch enabled returns results from solr.
    And global search expects the query "content" and will return:
      | nothing |
    And I am on the "Sharing Studio" "openstudio activity" page
    And I set the field "Search My Module" to "content"
    When I submit the openstudio search form "#openstudio_searchquery" "css_element"
    Then I should not see "content — 2 results found"
    And I should see "Search result for terms:"
    And I should see "No results? Try searching from within My Module"

  @javascript
  Scenario: Search my folder
    When I am logged in as "student1"

    Given the following open studio "folders" exist:
      | openstudio | user     | name                     | description                      | visibility | contenttype    |
      | OS1        | student1 | Student content folder 1 | My Folder Overview Description 1 | private    | folder_content |
      | OS1        | student1 | Student content folder 2 | My Folder Overview Description 2 | module     | folder_content |

    # Check searching with moodleglobalsearch enabled returns results from solr.
    And global search expects the query "folder" and will return:
      | nothing |
    And I am on the "Sharing Studio" "openstudio activity" page
    And I set the field "Search My Module" to "folder"
    When I submit the openstudio search form "#openstudio_searchquery" "css_element"
    Then I should not see "folder — 1 results found"
    And I should see "Search result for terms:"
    And I should see "No results? Try searching from within My Module"

  @javascript
  Scenario: Global search
    When I am logged in as "student1"

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

    Then I am on the "Sharing Studio" "openstudio activity" page
    And I set the field "Search My Module" to "keyword"
    When I submit the openstudio search form "#openstudio_searchquery" "css_element"
    And I should see "keyword — 11 results found"
    And I should see "My Content 1"
    Then I should see "My Content 9"

  @javascript
  Scenario: Search for a tag
    Given I am logged in as "student1"
    And global search expects the query "tag1" and will return:
      | nothing |
    And I am on the "Sharing Studio" "openstudio activity" page
    And I follow "Student content 3"
    And I press "Edit"
    And I press "Add file"
    And I set the field "Tags" to "tag1"
    And I wait until ".form-autocomplete-selection span[data-value='tag1']" "css_element" exists
    And I press "Save"
    And I wait until the page is ready
    When I follow "tag1"
    Then I should see "tag1 — No results"

  @javascript
  Scenario: Search with filter
    When I am logged in as "admin"

    And the following open studio "folders" exist:
      | openstudio | user     | name             | description                         | visibility | contenttype    | index | keyword |
      | OS1        | student1 | Student folder 2 | My Folder Overview Description 2    | module     | folder_content | 1     | keyword |
      | OS1        | student1 | Student folder 3 | My Folder Overview Description 3    | module     | folder_content | 1     | keyword |
      | OS1        | admin    | Admin folder 1   | Admin Folder Overview Description 1 | module     | folder_content | 1     | keyword |
    And the following open studio "contents" exist:
      | openstudio | user     | name            | description                       | visibility | index | keyword |
      | OS1        | student1 | My Content 1    | Test My Content Details View 1    | module     | 1     | keyword |
      | OS1        | student1 | My Content 2    | Test My Content Details View 2    | module     | 2     | keyword |
      | OS1        | student1 | My Content 3    | Test My Content Details View 3    | module     | 3     | keyword |
      | OS1        | student1 | My Content 4    | Test My Content Details View 4    | module     | 4     | keyword |
      | OS1        | student1 | My Content 5    | Test My Content Details View 5    | module     | 5     | keyword |
      | OS1        | student1 | My Content 6    | Test My Content Details View 6    | module     | 6     | keyword |
      | OS1        | student1 | My Content 7    | Test My Content Details View 7    | module     | 7     | keyword |
      | OS1        | student1 | My Content 8    | Test My Content Details View 8    | module     | 8     | keyword |
      | OS1        | student1 | My Content 9    | Test My Content Details View 9    | module     | 9     | keyword |
      | OS1        | admin    | Admin Content 1 | Test Admin Content Details View 1 | module     | 10    | keyword |
    And the following open studio "comments" exist:
      | openstudio | user     | content         | comment                      | index | keyword |
      | OS1        | student1 | My Content 1    | My Notification comment 1    | 1     | keyword |
      | OS1        | student1 | My Content 2    | My Notification comment 2    | 1     | keyword |
      | OS1        | admin    | Admin Content 1 | Admin Notification comment 1 | 1     | keyword |

    Then I am on the "Sharing Studio" "openstudio activity" page
    And I set the field "Search My Module" to "keyword"
    When I submit the openstudio search form "#openstudio_searchquery" "css_element"
    And I press "Filter"
    And I click on "input#openstudio_filter_from_3" "css_element"
    And I press "Apply"
    And I should not see "Admin Content 1"
    And I should see "My Content 1"
    And I click on "input#openstudio_filter_from_2" "css_element"
    And I press "Apply"
    And I should see "Admin Content 1"
    And I should not see "My Content 1"
    And I click on "input#openstudio_filter_types_100" "css_element"
    And I press "Apply"
    And I should see "Admin folder 1"
    And I should not see "Student folder 1"
    And I click on "input#openstudio_filter_from_1" "css_element"
    And I press "Apply"
    And I should see "Student folder 2"
    And I should not see "My Content 1"
    And I click on "input#openstudio_filter_types_0" "css_element"
    And I click on "input#openstudio_filter_user_flags_8" "css_element"
    And I press "Apply"
    And I should see "My Content 1"
    And I should see "My Content 2"
    And I click on "input#openstudio_filter_from_2" "css_element"
    And I press "Apply"
    And I should see "Admin Content 1"
    And I click on "input#openstudio_filter_user_flags_8" "css_element"
    And I click on "input#openstudio_filter_user_flags_5" "css_element"
    And I press "Apply"
    And I should see "No results?"

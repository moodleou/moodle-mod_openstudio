@ou @ou_vle @mod @mod_openstudio @javascript @mod_openstudio_notifications
Feature: Open Studio notifications
  In order to track activity on content I am interested in
  As a student
  I want recive notifications about my posts and comments

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | teacher2 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
      | student2 | Student   | 2        | student2@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
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
      | user     | group |
      | teacher1 | G1    |
      | student1 | G1    |
      | student2 | G1    |
    And the following open studio "instances" exist:
      | course | name                | description                | grouping | groupmode | pinboard | idnumber | tutorroles     | id_tutorrolesgroup_5 |
      | C1     | Notification Studio | Notifification description | GI1      | 1         | 99       | OS1      | editingteacher | 1                    |
    And all users have accepted the plagarism statement for "OS1" openstudio

  Scenario: Notify tutor when a post is shared with them
    Given I am on the "Notification Studio" "openstudio activity" page logged in as "student1"
    And I follow "Add new content"
    And I set the following fields to these values:
      | My Module   | 1           |
      | Title       | Module post |
      | Description | Module post |
    And I press "Save"
    And I follow "My Content"
    And I follow "Add new content"
    And I set the following fields to these values:
      | My Tutor    | 1          |
      | Title       | Tutor post |
      | Description | Tutor post |
    And I press "Save"
    When I am on the "Notification Studio" "openstudio activity" page logged in as "teacher1"
    Then I should see "1" in the ".openstudio-navigation-notification-number" "css_element"
    When I click on "Notifications" "button"
    Then I should see "Tutor post" in the ".openstudio-notifications-list" "css_element"
    And I should not see "Module post" in the ".openstudio-notifications-list" "css_element"

  Scenario: Notify a user when another user flags on their post
    Given the following open studio "contents" exist:
      | openstudio | user     | name                | description       | visibility |
      | OS1        | student1 | Notification post 1 | lorem ipsum dolor | module     |
      | OS1        | student1 | Notification post 2 | lorem ipsum dolor | module     |
    And "student1" will recieve notifications for openstudio content "Notification post 1"
    And "student1" will recieve notifications for openstudio content "Notification post 2"
    And I am on the "Notification Studio" "openstudio activity" page logged in as "student2"
    And I follow "Notification post 1"
    And I click on "0 Smiles" "text"
    When I am on the "Notification Studio" "openstudio activity" page logged in as "student1"
    Then I should see "1" in the ".openstudio-navigation-notification-number" "css_element"
    When I click on "Notifications" "button"
    Then I should see "Notification post 1" in the ".openstudio-notifications-list" "css_element"
    Then I should see "liked your post" in the ".openstudio-notifications-list" "css_element"
    And I should not see "Notification post 2" in the ".openstudio-notifications-list" "css_element"

  Scenario: Notify a user when another user comments on their post
    Given the following open studio "contents" exist:
      | openstudio | user     | name                | description       | visibility |
      | OS1        | student1 | Notification post 1 | lorem ipsum dolor | module     |
      | OS1        | student1 | Notification post 2 | lorem ipsum dolor | module     |
    And "student1" will recieve notifications for openstudio content "Notification post 1"
    And "student1" will recieve notifications for openstudio content "Notification post 2"
    And I am on the "Notification Studio" "openstudio activity" page logged in as "student2"
    And I follow "Notification post 1"
    And I press "Add new comment"
    And I set the field "Comment" to "Test comment"
    And I press "Post comment"
    When I am on the "Notification Studio" "openstudio activity" page logged in as "student1"
    Then I should see "1" in the ".openstudio-navigation-notification-number" "css_element"
    When I click on "Notifications" "button"
    Then I should see "Notification post 1" in the ".openstudio-notifications-list" "css_element"
    Then I should see "left a comment on" in the ".openstudio-notifications-list" "css_element"
    And I should not see "Notification post 2" in the ".openstudio-notifications-list" "css_element"

  Scenario: Notify a user when another user replies to their comment
    Given the following open studio "contents" exist:
      | openstudio | user     | name                | description       | visibility |
      | OS1        | student1 | Notification post 1 | lorem ipsum dolor | module     |
    And "student1" will recieve notifications for openstudio content "Notification post 1"
    And the following open studio "comments" exist:
      | openstudio | user     | content             | comment                |
      | OS1        | student2 | Notification post 1 | Notification comment 1 |
    And "student2" will recieve notifications for openstudio comment "Notification comment 1"
    And I am on the "Notification Studio" "openstudio activity" page logged in as "student1"
    And I follow "Notification post 1"
    And I press "Reply"
    And I set the field "Comment" to "Test comment"
    And I wait until the page is ready
    And I press "Post comment"
    When I am on the "Notification Studio" "openstudio activity" page logged in as "student2"
    Then I should see "1" in the ".openstudio-navigation-notification-number" "css_element"
    When I click on "Notifications" "button"
    Then I should see "Notification post 1" in the ".openstudio-notifications-list" "css_element"
    Then I should see "replied to a comment" in the ".openstudio-notifications-list" "css_element"

  Scenario: Notify a user when another user flags their comment
    Given the following open studio "contents" exist:
      | openstudio | user     | name                | description       | visibility |
      | OS1        | student1 | Notification post 1 | lorem ipsum dolor | module     |
    And "student1" will recieve notifications for openstudio content "Notification post 1"
    And the following open studio "comments" exist:
      | openstudio | user     | content             | comment                |
      | OS1        | student2 | Notification post 1 | Notification comment 1 |
    And "student2" will recieve notifications for openstudio comment "Notification comment 1"
    And I am on the "Notification Studio" "openstudio activity" page logged in as "student1"
    And I follow "Notification post 1"
    And I click on "Like comment" "link"
    When I am on the "Notification Studio" "openstudio activity" page logged in as "student2"
    Then I should see "1" in the ".openstudio-navigation-notification-number" "css_element"
    When I click on "Notifications" "button"
    Then I should see "Notification post 1" in the ".openstudio-notifications-list" "css_element"
    Then I should see "liked a comment" in the ".openstudio-notifications-list" "css_element"

  Scenario: Mark notifications as read
    Given the following open studio "contents" exist:
      | openstudio | user     | name                | description       | visibility |
      | OS1        | student1 | Notification post 1 | lorem ipsum dolor | module     |
    And "student1" will recieve notifications for openstudio content "Notification post 1"
    And the following open studio "comments" exist:
      | openstudio | user     | content             | comment                |
      | OS1        | student2 | Notification post 1 | Notification comment 1 |
    And "student2" will recieve notifications for openstudio comment "Notification comment 1"
    And I am on the "Notification Studio" "openstudio activity" page logged in as "student1"
    And I follow "Notification post 1"
    And I click on "Like comment" "link"
    And I press "Reply"
    And I wait until the page is ready
    And I set the field "Comment" to "Test comment"
    And I wait until the page is ready
    And I press "Post comment"
    When I am on the "Notification Studio" "openstudio activity" page logged in as "student2"
    And I wait until the page is ready
    Then I should see "2" in the ".openstudio-navigation-notification-number" "css_element"
    When I click on "Notifications" "button"
    And I reload the page
    Then ".openstudio-navigation-notification-number" "css_element" should not exist

  Scenario: Stop following a post
    Given the following open studio "contents" exist:
      | openstudio | user     | name                | description       | visibility |
      | OS1        | student1 | Notification post 1 | lorem ipsum dolor | module     |
      | OS1        | student1 | Notification post 2 | lorem ipsum dolor | module     |
    And "student1" will recieve notifications for openstudio content "Notification post 1"
    And "student1" will recieve notifications for openstudio content "Notification post 2"
    And I am on the "Notification Studio" "openstudio activity" page logged in as "student2"
    And I follow "Notification post 1"
    And I click on "0 Smiles" "text"
    When I am on the "Notification Studio" "openstudio activity" page logged in as "student1"
    Then I should see "1" in the ".openstudio-navigation-notification-number" "css_element"
    When I press "Notifications"
    And I press "Stop notifications for this content"
    And I click on "Stop notifications" "button" in the ".modal" "css_element"
    Then "Stop notifications for this content" "button" should not exist
    Given I am on the "Notification Studio" "openstudio activity" page logged in as "student2"
    And I follow "Notification post 1"
    And I click on "0 Inspired" "text"
    When I am on the "Notification Studio" "openstudio activity" page logged in as "student1"
    Then ".openstudio-navigation-notification-number" "css_element" should not exist

  Scenario: Stop following a comment thread
    Given the following open studio "contents" exist:
      | openstudio | user     | name                | description       | visibility |
      | OS1        | student1 | Notification post 1 | lorem ipsum dolor | module     |
    And "student1" will recieve notifications for openstudio content "Notification post 1"
    And the following open studio "comments" exist:
      | openstudio | user     | content             | comment                |
      | OS1        | student2 | Notification post 1 | Notification comment 1 |
    And "student2" will recieve notifications for openstudio comment "Notification comment 1"
    And I am on the "Notification Studio" "openstudio activity" page logged in as "student1"
    And I follow "Notification post 1"
    And I click on "Like comment" "link"
    And I am on the "Notification Studio" "openstudio activity" page logged in as "student2"
    And I should see "1" in the ".openstudio-navigation-notification-number" "css_element"
    And I press "Notifications"
    And I press "Stop notifications for this comment"
    And I click on "Stop notifications" "button" in the ".modal" "css_element"
    And "Stop notifications for this comment" "button" should not exist
    And I am on the "Notification Studio" "openstudio activity" page logged in as "teacher1"
    And I follow "Notification post 1"
    When I click on "Like comment" "link"
    And I am on the "Notification Studio" "openstudio activity" page logged in as "student2"
    Then ".openstudio-navigation-notification-number" "css_element" should not exist

  Scenario: Delete unread notifications when a post is deleted
    Given I am on the "Notification Studio" "openstudio activity" page logged in as "student1"
    And I follow "Add new content"
    And I set the following fields to these values:
      | My Module   | 1           |
      | Title       | Module post |
      | Description | Module post |
    And I press "Save"
    And I follow "My Content"
    And I follow "Add new content"
    And I set the following fields to these values:
      | My Tutor    | 1          |
      | Title       | Tutor post |
      | Description | Tutor post |
    And I press "Save"
    And I am on the "Notification Studio" "openstudio activity" page logged in as "teacher1"
    And I should see "1" in the ".openstudio-navigation-notification-number" "css_element"
    And I follow "Shared Content > My Group" in the openstudio navigation
    And I click on "Tutor post" "link" in the "openstudio_grid" "region"
    And I click on "0 Smiles" "text"
    And I am on the "Notification Studio" "openstudio activity" page logged in as "student1"
    And I should see "1" in the ".openstudio-navigation-notification-number" "css_element"
    And I follow "My Content" in the openstudio navigation
    And I click on "Tutor post" "link" in the "openstudio_grid" "region"
    When I press "Delete"
    And I click on ".openstudio-delete-ok-btn" "css_element"
    Then ".openstudio-navigation-notification-number" "css_element" should not exist
    When I am on the "Notification Studio" "openstudio activity" page logged in as "teacher1"
    Then ".openstudio-navigation-notification-number" "css_element" should not exist

  Scenario: Delete unread notifications when a flag is removed
    Given the following open studio "contents" exist:
      | openstudio | user     | name                | description       | visibility |
      | OS1        | student1 | Notification post 1 | lorem ipsum dolor | module     |
    And "student1" will recieve notifications for openstudio content "Notification post 1"
    And I am on the "Notification Studio" "openstudio activity" page logged in as "student2"
    And I follow "Notification post 1"
    And I click on "0 Smiles" "text"
    And I am on the "Notification Studio" "openstudio activity" page logged in as "student1"
    And I should see "1" in the ".openstudio-navigation-notification-number" "css_element"
    And I am on the "Notification Studio" "openstudio activity" page logged in as "student2"
    And I follow "Notification post 1"
    And I click on "1 Smiles" "text"
    And I am on the "Notification Studio" "openstudio activity" page logged in as "student1"
    Then ".openstudio-navigation-notification-number" "css_element" should not exist

  Scenario: Delete unread notifications when a comment is deleted
    Given the following open studio "contents" exist:
      | openstudio | user     | name                | description       | visibility |
      | OS1        | student1 | Notification post 1 | lorem ipsum dolor | module     |
    And "student1" will recieve notifications for openstudio content "Notification post 1"
    And I am on the "Notification Studio" "openstudio activity" page logged in as "student2"
    And I follow "Notification post 1"
    And I press "Add new comment"
    And I set the field "Comment" to "Test comment"
    And I press "Post comment"
    And I am on the "Notification Studio" "openstudio activity" page logged in as "student1"
    Then ".openstudio-navigation-notification-number" "css_element" should exist
    When I am on the "Notification Studio" "openstudio activity" page logged in as "student2"
    And I follow "Notification post 1"
    And I follow "Delete comment"
    And I click on ".openstudio-comment-delete-btn" "css_element"
    And I am on the "Notification Studio" "openstudio activity" page logged in as "student1"
    Then ".openstudio-navigation-notification-number" "css_element" should not exist

  Scenario: Notification for folder in My Activities views
    Given the following open studio "level1s" exist:
      | openstudio | name   | sortorder |
      | OS1        | Block1 | 1         |
    And the following open studio "level2s" exist:
      | level1 | name      | sortorder |
      | Block1 | Activity1 | 1         |
    And the following open studio "level3s" exist:
      | level2    | name       | sortorder | contenttype |
      | Activity1 | Content1.1 | 1         | folder      |
    And I am on the "Notification Studio" "openstudio activity" page logged in as "student2"
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "Content1.1" "link" in the ".openstudio-grid-item" "css_element"
    And I follow "Edit folder details and sharing"
    And I set the field "My Module" to "1"
    And I set the field "Folder title" to "Content student 2"
    And I press "Save"
    And I am on the "Notification Studio" "openstudio activity" page logged in as "student1"
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "Content1.1" "link" in the ".openstudio-grid-item" "css_element"
    And I follow "Edit folder details and sharing"
    And I set the field "My Module" to "1"
    And I set the field "Folder title" to "Content student 1"
    And I press "Save"
    And I follow "Shared Content > My Module" in the openstudio navigation
    And I follow "Content student 2"
    And I click on "0 Smiles" "text"
    And I am on the "Notification Studio" "openstudio activity" page logged in as "student2"
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "Content student 2" "link" in the ".openstudio-grid-item" "css_element"
    And I should see "1" in the ".openstudio-navigation-notification-number" "css_element"

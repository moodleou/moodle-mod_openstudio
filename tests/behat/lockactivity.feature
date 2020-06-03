@ou @ou_vle @mod @mod_openstudio @javascript
Feature: Locking activity contents
  In order to control when students can submit work
  As a teacher
  I need to set lock and unlock times for pre-defined contents

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Student   | 1        | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following open studio "instances" exist:
      | course | name             | description                  | pinboard | idnumber | tutorroles | allowlatesubmissions | latesubmissionmessage |
      | C1     | Sharing Studio   | Sharing Studio description   | 99       | OS1      | manager    | 0                    | late message          |
      | C1     | Sharing Studio 2 | Sharing Studio 2 description | 99       | OS2      | manager    | 1                    | late message          |
    And the following open studio "level1s" exist:
      | openstudio | name | sortorder |
      | OS1        | B1   | 1         |
      | OS2        | B2   | 1         |
    And the following open studio "level2s" exist:
      | openstudio | level1 | name | sortorder |
      | OS1        | B1     | A1   | 1         |
      | OS2        | B2     | A2   | 1         |
    And all users have accepted the plagarism statement for "OS1" openstudio
    And all users have accepted the plagarism statement for "OS2" openstudio

  Scenario: Locked
    Given the following open studio "level3s" exist:
      | openstudio | level2 | name    | sortorder | locktype | locktime | unlocktime |
      | OS1        | A1     | all     | 1         | all      | -1 day   |            |
      | OS1        | A1     | crud    | 2         | crud     | -1 day   |            |
      | OS1        | A1     | social  | 3         | social   | -1 day   |            |
      | OS1        | A1     | comment | 4         | comment  | -1 day   |            |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Sharing Studio"
    And I follow "My Content > My Activities" in the openstudio navigation
    Then I should see "This activity is locked" in the ".openstudio-grid-item:nth-child(1)" "css_element"
    And I should see "This activity is locked" in the ".openstudio-grid-item:nth-child(2)" "css_element"
    And I should not see "This activity is locked" in the ".openstudio-grid-item:nth-child(3)" "css_element"
    And I should not see "This activity is locked" in the ".openstudio-grid-item:nth-child(4)" "css_element"
    And ".openstudio-upload-container" "css_element" should not exist in the ".openstudio-grid-item:nth-child(1)" "css_element"
    And ".openstudio-upload-container" "css_element" should not exist in the ".openstudio-grid-item:nth-child(2)" "css_element"
    And ".openstudio-upload-container" "css_element" should exist in the ".openstudio-grid-item:nth-child(3)" "css_element"
    And ".openstudio-upload-container" "css_element" should exist in the ".openstudio-grid-item:nth-child(4)" "css_element"

    # late submissions allowed
    Given the following open studio "level3s" exist:
      | openstudio | level2 | name    | sortorder | locktype | locktime | unlocktime |
      | OS2        | A2     | all     | 1         | all      | -1 day   |            |
      | OS2        | A2     | crud    | 2         | crud     | -1 day   |            |
      | OS2        | A2     | social  | 3         | social   | -1 day   |            |
      | OS2        | A2     | comment | 4         | comment  | -1 day   |            |
    And I am on "Course 1" course homepage
    And I follow "Sharing Studio 2"
    And I follow "My Content > My Activities" in the openstudio navigation
    Then I should see "late message" in the ".openstudio-grid-item:nth-child(1)" "css_element"
    And I should see "late message" in the ".openstudio-grid-item:nth-child(2)" "css_element"
    And I should not see "late message" in the ".openstudio-grid-item:nth-child(3)" "css_element"
    And I should not see "late message" in the ".openstudio-grid-item:nth-child(4)" "css_element"
    And ".openstudio-upload-container" "css_element" should exist in the ".openstudio-grid-item:nth-child(1)" "css_element"
    And ".openstudio-upload-container" "css_element" should exist in the ".openstudio-grid-item:nth-child(2)" "css_element"
    And ".openstudio-upload-container" "css_element" should exist in the ".openstudio-grid-item:nth-child(3)" "css_element"
    And ".openstudio-upload-container" "css_element" should exist in the ".openstudio-grid-item:nth-child(4)" "css_element"

  Scenario: Locked until tomorrow
    Given the following open studio "level3s" exist:
      | openstudio | level2 | name   | sortorder | locktype | locktime | unlocktime |
      | OS1        | A1     | locked | 1         | all      | -1 day   | +1 day     |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Sharing Studio"
    And I follow "My Content > My Activities" in the openstudio navigation
    Then I should see "locked" in the ".openstudio-grid-item" "css_element"
    And I should see "This activity is locked"
    And ".openstudio-upload-container" "css_element" should not exist in the ".openstudio-grid-item" "css_element"

  Scenario: Locked starting from tomorrow
    Given the following open studio "level3s" exist:
      | openstudio | level2 | name         | sortorder | locktype | locktime | unlocktime |
      | OS2        | A2     | futurelocked | 1         | all      | +1 day   |            |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Sharing Studio 2"
    And I follow "My Content > My Activities" in the openstudio navigation
    Then I should see "futurelocked" in the ".openstudio-grid-item" "css_element"
    And I should see "This activity will be locked"
    And ".openstudio-upload-container" "css_element" should exist in the ".openstudio-grid-item" "css_element"

  Scenario: Unlocked
    Given the following open studio "level3s" exist:
      | openstudio | level2 | name     | sortorder | locktype | locktime | unlocktime |
      | OS2        | A2     | unlocked | 1         | all      | -2 days  | -1 day     |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Sharing Studio 2"
    And I follow "My Content > My Activities" in the openstudio navigation
    Then I should see "unlocked" in the ".openstudio-grid-item" "css_element"
    And I should see "This activity will be locked"
    And ".openstudio-upload-container" "css_element" should exist in the ".openstudio-grid-item" "css_element"
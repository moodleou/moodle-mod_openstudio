@ou @ou_vle @mod @mod_openstudio @mod_openstudio_manage_folders
Feature: Subscribe/Unsubscribe to my studio
  In order to subscribe/unsubscribe to my studio
  As a student
  I need to be able to subscribe/unsubscribe to my studio


  Background: Setup course and studio
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | student1 | Student | 1 | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |

    # Enable REST web service
    When I am on site homepage
    And I log in as "admin"
    And I follow "Site administration"
    And I follow "Advanced features"
    And I set the field "Enable web services" to "1"
    And I press "Save changes"
    When I navigate to "Overview" node in "Site administration > Plugins > Web services"
    And I click on "Enable protocols" "link" in the "onesystemcontrol" "table"
    And I click on "Enable" "link" in the "REST protocol" "table_row"
    And I press "Save changes"

    And I am on site homepage
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "OpenStudio 2 (pilot only)" to section "1" and I fill the form with:
      | Name | Test Open Studio name 1 |
      | Description | Test Open Studio description |
      | Enable pinboard | 99 |
      | Enable Folders  | 1 |
      | Abuse reports are emailed to | teacher1@asd.com |
      | ID number                    | OS1              |

    And I am on site homepage
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage

  @javascript
  Scenario: Subscribe/Unsubscribe
    # Subscribe
    When I follow "Test Open Studio name 1"
    And I press "Accept"
    Then I should see "Subscribe to my studio"

    And I click on "button[name='subscribebutton']" "css_element"
    Then I should see "Subscription settings"

    And I click on ".openstudio-subscript-btn" "css_element"
    Then I should see "Unsubscribe"

    # Unsubscribe
    And I reload the page
    Then I should see "Unsubscribe"

    And I click on "button[name='subscribebutton']" "css_element"
    Then I should see "Subscribe to my studio"

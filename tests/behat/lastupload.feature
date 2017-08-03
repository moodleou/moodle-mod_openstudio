@ou @ou_vle @mod @mod_openstudio @mod_openstudio_test_content @_file_upload @javascript
Feature: Show last upload time for Open Studio activities

    Background: Setup course and studio
      Given the following "users" exist:
          | username | firstname | lastname | email |
          | teacher1 | Teacher | 1 | teacher1@asd.com |
          | student1 | Student | 1 | student1@asd.com |
          | student2 | Student | 2 | student2@asd.com |
          | student3 | Student | 3 | student3@asd.com |
      And the following "courses" exist:
          | fullname | shortname | category |
          | Course 1 | C1 | 0 |
      And the following "course enrolments" exist:
          | user | course | role |
          | teacher1 | C1 | editingteacher |
          | student1 | C1 | student |
          | student2 | C1 | student |
          | student3 | C1 | student |
      And the following "groups" exist:
          | name   | course | idnumber |
          | group1 | C1     | G1       |
          | group2 | C1     | G2       |
          | group3 | C1     | G3       |
      And the following "groupings" exist:
          | name      | course | idnumber |
          | grouping1 | C1     | GI1      |
      And the following "grouping groups" exist:
          | grouping | group |
          | GI1      | G1    |
          | GI1      | G2    |
          | GI1      | G3    |
      And the following "group members" exist:
          | user     | group  |
          | teacher1 | G1 |
          | student1 | G1 |
          | student2 | G1 |
          | teacher1 | G2 |
          | student2 | G2 |
          | student3 | G2 |
          | teacher1 | G3 |
          | student3 | G3 |
      And I log in as "teacher1"
      And I am on site homepage
      And I am on "Course 1" course homepage
      And I turn editing mode on
      And I add the "OU Recent activity" block
      And I add a "OpenStudio 2 (pilot only)" to section "1" and I fill the form with:
          | Name                         | Test Open Studio name 1        |
          | Description                  | Test Open Studio description 1 |
          | Group mode                   | Visible groups                 |
          | Grouping                     | grouping1                      |
          | Enable pinboard              | 99                             |
          | Abuse reports are emailed to | teacher1@asd.com               |
          | ID number                    | OS1                            |
      And I add a "OpenStudio 2 (pilot only)" to section "2" and I fill the form with:
          | Name                         | Test Open Studio name 2        |
          | Description                  | Test Open Studio description 2 |
          | Group mode                   | Visible groups                 |
          | Grouping                     | grouping1                      |
          | Enable pinboard              | 99                             |
          | Abuse reports are emailed to | teacher1@asd.com               |
          | ID number                    | OS2                            |
      And I add a "OpenStudio 2 (pilot only)" to section "3" and I fill the form with:
          | Name                         | Test Open Studio name 3        |
          | Description                  | Test Open Studio description 3 |
          | Group mode                   | Visible groups                 |
          | Grouping                     | grouping1                      |
          | Enable pinboard              | 99                             |
          | Abuse reports are emailed to | teacher1@asd.com               |
          | ID number                    | OS3                            |
      And Open Studio test instance is configured for "Test Open Studio name 1"
      And Open Studio test instance is configured for "Test Open Studio name 2"
      And Open Studio test instance is configured for "Test Open Studio name 3"
      And all users have accepted the plagarism statement for "OS1" openstudio
      And all users have accepted the plagarism statement for "OS2" openstudio
      And all users have accepted the plagarism statement for "OS3" openstudio

    Scenario: Test rendering last upload status on Recent Activity Block when a new content is added with a visibility of My Module.
      Then "/descendant::div[@class='activityinstance'][1]//span[@class='lastmodtext studiolmt']" "xpath_element" should not exist
      And I follow "Test Open Studio name 1"
      And I follow "Add new content"
      And I press "Add file"
      And I set the following fields to these values:
        | Who can view this content | My module                                  |
        | Title                     | Test My Group Board View 1                 |
        | Description               | My Group Board View Description 1          |
        | Upload content            | mod/openstudio/tests/importfiles/test1.jpg |
      And I press "Save"
      And I am on site homepage
      And I am on "Course 1" course homepage
      And I follow "Test Open Studio name 2"
      And I follow "Add new content"
      And I press "Add file"
      And I set the following fields to these values:
        | Who can view this content | My module                                  |
        | Title                     | Test My Group Board View 2                 |
        | Description               | My Group Board View Description 2          |
        | Upload content            | mod/openstudio/tests/importfiles/test2.jpg |
      And I press "Save"
      And I am on site homepage
      And I am on "Course 1" course homepage
      And "OU Recent activity" "block" should exist
      Then "/descendant::div[@class='activityinstance'][1]//span[@class='lastmodtext studiolmt']" "xpath_element" should exist
      And I should see "Test Open Studio name 1" in the ".ourecent_list li:nth-child(2) .instancename" "css_element"
      And I should see "Test Open Studio name 2" in the ".ourecent_list li:nth-child(1) .instancename" "css_element"
      And I should not see "Test Open Studio name 3" in the ".ourecent_list li:nth-child(1) .instancename" "css_element"

      Given I am on site homepage
      When I log out
      And I log in as "student1"
      And I am on "Course 1" course homepage
      And "OU Recent activity" "block" should exist
      Then "/descendant::div[@class='activityinstance'][1]//span[@class='lastmodtext studiolmt']" "xpath_element" should exist
      And I should see "Test Open Studio name 1" in the ".ourecent_list li:nth-child(2) .instancename" "css_element"
      And I should see "Test Open Studio name 2" in the ".ourecent_list li:nth-child(1) .instancename" "css_element"
      And I should not see "Test Open Studio name 3" in the ".ourecent_list li:nth-child(1) .instancename" "css_element"

      When I am on site homepage
      And I log out
      And I log in as "student3"
      And I am on "Course 1" course homepage
      Then "/descendant::div[@class='activityinstance'][1]//span[@class='lastmodtext studiolmt']" "xpath_element" should exist
      And I should see "Test Open Studio name 1" in the ".ourecent_list li:nth-child(2) .instancename" "css_element"
      And I should see "Test Open Studio name 2" in the ".ourecent_list li:nth-child(1) .instancename" "css_element"
      And I should not see "Test Open Studio name 3" in the ".ourecent_list li:nth-child(1) .instancename" "css_element"

    Scenario: Recent activity block display with sharing level
      Then "/descendant::div[@class='activityinstance'][1]//span[@class='lastmodtext studiolmt']" "xpath_element" should not exist

      # Add new content in My Module View
      And I follow "Test Open Studio name 1"
      And I follow "Add new content"
      And I press "Add file"
      And I set the following fields to these values:
        | Who can view this content | My module                                  |
        | Title                     | Test My Group Board View 1                 |
        | Description               | My Group Board View Description 1          |
        | Upload content            | mod/openstudio/tests/importfiles/test1.jpg |
      And I press "Save"

      # Add new content in My Pinboard View
      And I am on site homepage
      And I am on "Course 1" course homepage
      And I follow "Test Open Studio name 2"
      And I follow "Add new content"
      And I press "Add file"
      And I set the following fields to these values:
        | Who can view this content | Only me |
        | Title | Test My Group Board View 2 |
        | Description | My Group Board View Description 2 |
        | Upload content | mod/openstudio/tests/importfiles/test2.jpg |
      And I press "Save"

      # Add new content in My Group View
      And I am on site homepage
      And I am on "Course 1" course homepage
      And I follow "Test Open Studio name 3"
      And I follow "Add new content"
      And I press "Add file"
      And I set the following fields to these values:
        | Who can view this content | Group - group1 |
        | Title | Test My Group Board View 3 |
        | Description | My Group Board View Description 3 |
        | Upload content | mod/openstudio/tests/importfiles/test2.jpg |
      And I press "Save"  

      And I am on site homepage
      And I am on "Course 1" course homepage
      And "OU Recent activity" "block" should exist
      Then "/descendant::div[@class='activityinstance'][1]//span[@class='lastmodtext studiolmt']" "xpath_element" should exist
      And I should see "Test Open Studio name 1" in the ".ourecent_list li:nth-child(3) .instancename" "css_element"
      And I should see "Test Open Studio name 2" in the ".ourecent_list li:nth-child(2) .instancename" "css_element"
      And I should see "Test Open Studio name 3" in the ".ourecent_list li:nth-child(1) .instancename" "css_element"

      # Switch student1 user
      When I am on site homepage
      And I log out
      And I log in as "student1"
      And I am on "Course 1" course homepage
      And "OU Recent activity" "block" should exist
      Then "/descendant::div[@class='activityinstance'][1]//span[@class='lastmodtext studiolmt']" "xpath_element" should exist
      And I should see "Test Open Studio name 1" in the ".ourecent_list li:nth-child(2) .instancename" "css_element"
      And I should not see "Test Open Studio name 2" in the ".ourecent_list li:nth-child(2) .instancename" "css_element"
      And I should see "Test Open Studio name 3" in the ".ourecent_list li:nth-child(1) .instancename" "css_element"

      # Switch student3 user
      When I am on site homepage
      And I log out
      And I log in as "student3"
      And I am on "Course 1" course homepage
      Then "/descendant::div[@class='activityinstance'][1]//span[@class='lastmodtext studiolmt']" "xpath_element" should exist
      And I should see "Test Open Studio name 3" in the ".ourecent_list li:nth-child(1) .instancename" "css_element"
      And I should not see "Test Open Studio name 2" in the ".ourecent_list li:nth-child(1) .instancename" "css_element"
      And I should not see "Test Open Studio name 1" in the ".ourecent_list li:nth-child(1) .instancename" "css_element"

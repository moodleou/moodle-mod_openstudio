@ou @ou_vle @mod @mod_openstudio @mod_openstudio_test_content @_file_upload @javascript
Feature: Pagination Open Studio stream views

  Background: Setup course and studio
    Given the following "users" exist:
      | username  | firstname | lastname | email            |
      | teacher1  | Teacher   | 1        | teacher1@asd.com |
      | student1  | Student   | 1        | student1@asd.com |
      | student2  | Student   | 2        | student2@asd.com |
      | student3  | Student   | 3        | student3@asd.com |
      | student4  | Student   | 4        | student4@asd.com |
      | student5  | Student   | 1        | student5@asd.com |
      | student6  | Student   | 2        | student6@asd.com |
      | student7  | Student   | 3        | student7@asd.com |
      | student8  | Student   | 4        | student8@asd.com |
      | student9  | Student   | 1        | student5@asd.com |
      | student10 | Student   | 2        | student6@asd.com |
      | student11 | Student   | 3        | student7@asd.com |
      | student12 | Student   | 4        | student8@asd.com |
      | student13 | Student   | 1        | student5@asd.com |
      | student14 | Student   | 2        | student6@asd.com |
      | student15 | Student   | 3        | student7@asd.com |
      | student16 | Student   | 4        | student8@asd.com |
      | student17 | Student   | 1        | student5@asd.com |
      | student18 | Student   | 2        | student6@asd.com |
      | student19 | Student   | 3        | student7@asd.com |
      | student20 | Student   | 4        | student8@asd.com |
      | student21 | Student   | 2        | student6@asd.com |
      | student22 | Student   | 3        | student7@asd.com |
      | student23 | Student   | 4        | student8@asd.com |
      | student24 | Student   | 1        | student5@asd.com |
      | student25 | Student   | 2        | student6@asd.com |
      | student26 | Student   | 3        | student7@asd.com |
      | student27 | Student   | 4        | student8@asd.com |
      | student28 | Student   | 1        | student5@asd.com |
      | student29 | Student   | 2        | student6@asd.com |
      | student30 | Student   | 3        | student7@asd.com |
      | student31 | Student   | 4        | student8@asd.com |
      | student32 | Student   | 2        | student6@asd.com |
      | student33 | Student   | 3        | student7@asd.com |
      | student34 | Student   | 4        | student8@asd.com |
      | student35 | Student   | 1        | student5@asd.com |
      | student36 | Student   | 2        | student6@asd.com |
      | student37 | Student   | 3        | student7@asd.com |
      | student38 | Student   | 4        | student8@asd.com |
      | student39 | Student   | 1        | student5@asd.com |
      | student40 | Student   | 2        | student6@asd.com |
      | student41 | Student   | 3        | student7@asd.com |
      | student42 | Student   | 4        | student8@asd.com |
      | student43 | Student   | 4        | student8@asd.com |
      | student44 | Student   | 2        | student6@asd.com |
      | student45 | Student   | 3        | student7@asd.com |
      | student46 | Student   | 4        | student8@asd.com |
      | student47 | Student   | 1        | student5@asd.com |
      | student48 | Student   | 2        | student6@asd.com |
      | student49 | Student   | 3        | student7@asd.com |
      | student50 | Student   | 4        | student8@asd.com |
      | student51 | Student   | 1        | student5@asd.com |
      | student52 | Student   | 2        | student6@asd.com |
      | student53 | Student   | 3        | student7@asd.com |
      | student54 | Student   | 4        | student8@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
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
      | user     | group |
      | teacher1 | G1    |
      | student1 | G1    |
      | student2 | G1    |
      | teacher1 | G2    |
      | student2 | G2    |
      | student3 | G2    |
      | student3 | G3    |
    And I am on the "Course 1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I add a openstudio activity to course "Course 1" section "1" and I fill the form with:
      | Name                         | Test Open Studio name 1      |
      | Description                  | Test Open Studio description |
      | Group mode                   | Separate groups              |
      | Grouping                     | grouping1                    |
      | Enable pinboard              | 99                           |
      | Abuse reports are emailed to | teacher1@asd.com             |
      | ID number                    | OS1                          |
      | id_tutorrolesgroup_1         | 1                            |
    And Open Studio test instance is configured for "Test Open Studio name 1"
    And all users have accepted the plagarism statement for "OS1" openstudio

  Scenario: Test Pagination without contents
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    Then I should not see "Test content 12"
    Then I should not see "Next"

  Scenario: Test Pagination with contents
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And the following open studio "contents" exist:
      | openstudio | user     | name           | description                | visibility |
      | OS1        | student1 | Test content A | Test content 1 description | module     |
    And I wait "2" seconds
    And the following open studio "contents" exist:
      | openstudio | user     | name         | description                 | visibility |
      | OS1        | student1 | Test content | Test content 2 description  | module     |
      | OS1        | student1 | Test content | Test content 3 description  | module     |
      | OS1        | student1 | Test content | Test content 4 description  | module     |
      | OS1        | student1 | Test content | Test content 5 description  | module     |
      | OS1        | student1 | Test content | Test content 6 description  | module     |
      | OS1        | student1 | Test content | Test content 7 description  | module     |
      | OS1        | student1 | Test content | Test content 8 description  | module     |
      | OS1        | student1 | Test content | Test content 9 description  | module     |
      | OS1        | student1 | Test content | Test content 10 description | module     |
      | OS1        | student1 | Test content | Test content 11 description | module     |
      | OS1        | student1 | Test content | Test content 12 description | module     |
      | OS1        | student1 | Test content | Test content 13 description | module     |
      | OS1        | student1 | Test content | Test content 14 description | module     |
      | OS1        | student1 | Test content | Test content 15 description | module     |
      | OS1        | student1 | Test content | Test content 16 description | module     |
      | OS1        | student1 | Test content | Test content 17 description | module     |
      | OS1        | student1 | Test content | Test content 18 description | module     |
      | OS1        | student1 | Test content | Test content 19 description | module     |
      | OS1        | student1 | Test content | Test content 20 description | module     |
      | OS1        | student1 | Test content | Test content 21 description | module     |
      | OS1        | student1 | Test content | Test content 22 description | module     |
      | OS1        | student1 | Test content | Test content 23 description | module     |
      | OS1        | student1 | Test content | Test content 24 description | module     |
      | OS1        | student1 | Test content | Test content 25 description | module     |
      | OS1        | student1 | Test content | Test content 26 description | module     |
      | OS1        | student1 | Test content | Test content 27 description | module     |
      | OS1        | student1 | Test content | Test content 28 description | module     |
      | OS1        | student1 | Test content | Test content 29 description | module     |
      | OS1        | student1 | Test content | Test content 30 description | module     |
      | OS1        | student1 | Test content | Test content 31 description | module     |
      | OS1        | student1 | Test content | Test content 32 description | module     |
      | OS1        | student1 | Test content | Test content 33 description | module     |
      | OS1        | student1 | Test content | Test content 34 description | module     |
      | OS1        | student1 | Test content | Test content 35 description | module     |
      | OS1        | student1 | Test content | Test content 36 description | module     |
      | OS1        | student1 | Test content | Test content 37 description | module     |
      | OS1        | student1 | Test content | Test content 38 description | module     |
      | OS1        | student1 | Test content | Test content 39 description | module     |
      | OS1        | student1 | Test content | Test content 40 description | module     |
      | OS1        | student1 | Test content | Test content 41 description | module     |
      | OS1        | student1 | Test content | Test content 42 description | module     |
      | OS1        | student1 | Test content | Test content 43 description | module     |
      | OS1        | student1 | Test content | Test content 44 description | module     |
      | OS1        | student1 | Test content | Test content 45 description | module     |
      | OS1        | student1 | Test content | Test content 46 description | module     |
      | OS1        | student1 | Test content | Test content 47 description | module     |
      | OS1        | student1 | Test content | Test content 48 description | module     |
      | OS1        | student1 | Test content | Test content 49 description | module     |
      | OS1        | student1 | Test content | Test content 50 description | module     |
      | OS1        | student1 | Test content | Test content 51 description | module     |
    And I wait "2" seconds
    And the following open studio "contents" exist:
      | openstudio | user     | name           | description                 | visibility |
      | OS1        | student1 | Test content N | Test content 13 description | module     |

    When I reload the page
    And I follow "People" in the openstudio navigation
    And I follow "Shared content > My Module" in the openstudio navigation
    And I set the field "View:" to "50"
    Then I should see "Test content N"
    And I should see "2"
    When I click on ".openstudio-desktop-paging .next" "css_element"
    Then I should see "Test content A"
    And I should not see "Test content N"
    And I should see "1"
    And I click on ".openstudio-desktop-paging .previous" "css_element"
    Then I should see "Test content N"

    # Mobile view
    When I change viewport size to "320x768" without runtime scaling
    And I reload the page
    And "Next" "link" should exist
    And I should not see "1" in the ".paging" "css_element"
    When I click on ".openstudio-mobile-paging-next" "css_element"
    Then I should see "Page 2" in the ".openstudio-mobile-paging-current" "css_element"
    Then I should see "Test content A"
    And I click on ".openstudio-mobile-paging-previous" "css_element"
    Then I should not see "Page 2"
    Then "Next" "link" should exist

  Scenario: Test Pagination numbers
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I wait "2" seconds
    And the following open studio "contents" exist:
      | openstudio | user     | name         | description                  | visibility |
      | OS1        | student1 | Test content | Test content 1 description   | module     |
      | OS1        | student1 | Test content | Test content 2 description   | module     |
      | OS1        | student1 | Test content | Test content 3 description   | module     |
      | OS1        | student1 | Test content | Test content 4 description   | module     |
      | OS1        | student1 | Test content | Test content 5 description   | module     |
      | OS1        | student1 | Test content | Test content 6 description   | module     |
      | OS1        | student1 | Test content | Test content 7 description   | module     |
      | OS1        | student1 | Test content | Test content 8 description   | module     |
      | OS1        | student1 | Test content | Test content 9 description   | module     |
      | OS1        | student1 | Test content | Test content 10 description  | module     |
      | OS1        | student1 | Test content | Test content 11 description  | module     |
      | OS1        | student1 | Test content | Test content 12 description  | module     |
      | OS1        | student1 | Test content | Test content 13 description  | module     |
      | OS1        | student1 | Test content | Test content 14 description  | module     |
      | OS1        | student1 | Test content | Test content 15 description  | module     |
      | OS1        | student1 | Test content | Test content 16 description  | module     |
      | OS1        | student1 | Test content | Test content 17 description  | module     |
      | OS1        | student1 | Test content | Test content 18 description  | module     |
      | OS1        | student1 | Test content | Test content 19 description  | module     |
      | OS1        | student1 | Test content | Test content 20 description  | module     |
      | OS1        | student1 | Test content | Test content 21 description  | module     |
      | OS1        | student1 | Test content | Test content 22 description  | module     |
      | OS1        | student1 | Test content | Test content 23 description  | module     |
      | OS1        | student1 | Test content | Test content 24 description  | module     |
      | OS1        | student1 | Test content | Test content 25 description  | module     |
      | OS1        | student1 | Test content | Test content 26 description  | module     |
      | OS1        | student1 | Test content | Test content 27 description  | module     |
      | OS1        | student1 | Test content | Test content 28 description  | module     |
      | OS1        | student1 | Test content | Test content 29 description  | module     |
      | OS1        | student1 | Test content | Test content 30 description  | module     |
      | OS1        | student1 | Test content | Test content 31 description  | module     |
      | OS1        | student1 | Test content | Test content 32 description  | module     |
      | OS1        | student1 | Test content | Test content 33 description  | module     |
      | OS1        | student1 | Test content | Test content 34 description  | module     |
      | OS1        | student1 | Test content | Test content 35 description  | module     |
      | OS1        | student1 | Test content | Test content 36 description  | module     |
      | OS1        | student1 | Test content | Test content 37 description  | module     |
      | OS1        | student1 | Test content | Test content 38 description  | module     |
      | OS1        | student1 | Test content | Test content 39 description  | module     |
      | OS1        | student1 | Test content | Test content 40 description  | module     |
      | OS1        | student1 | Test content | Test content 41 description  | module     |
      | OS1        | student1 | Test content | Test content 42 description  | module     |
      | OS1        | student1 | Test content | Test content 43 description  | module     |
      | OS1        | student1 | Test content | Test content 44 description  | module     |
      | OS1        | student1 | Test content | Test content 45 description  | module     |
      | OS1        | student1 | Test content | Test content 46 description  | module     |
      | OS1        | student1 | Test content | Test content 47 description  | module     |
      | OS1        | student1 | Test content | Test content 48 description  | module     |
      | OS1        | student1 | Test content | Test content 49 description  | module     |
      | OS1        | student1 | Test content | Test content 50 description  | module     |
      | OS1        | student1 | Test content | Test content 51 description  | module     |
      | OS1        | student1 | Test content | Test content 52 description  | module     |
      | OS1        | student1 | Test content | Test content 53 description  | module     |
      | OS1        | student1 | Test content | Test content 54 description  | module     |
      | OS1        | student1 | Test content | Test content 55 description  | module     |
      | OS1        | student1 | Test content | Test content 56 description  | module     |
      | OS1        | student1 | Test content | Test content 57 description  | module     |
      | OS1        | student1 | Test content | Test content 58 description  | module     |
      | OS1        | student1 | Test content | Test content 59 description  | module     |
      | OS1        | student1 | Test content | Test content 60 description  | module     |
      | OS1        | student1 | Test content | Test content 61 description  | module     |
      | OS1        | student1 | Test content | Test content 62 description  | module     |
      | OS1        | student1 | Test content | Test content 63 description  | module     |
      | OS1        | student1 | Test content | Test content 64 description  | module     |
      | OS1        | student1 | Test content | Test content 65 description  | module     |
      | OS1        | student1 | Test content | Test content 66 description  | module     |
      | OS1        | student1 | Test content | Test content 67 description  | module     |
      | OS1        | student1 | Test content | Test content 68 description  | module     |
      | OS1        | student1 | Test content | Test content 69 description  | module     |
      | OS1        | student1 | Test content | Test content 70 description  | module     |
      | OS1        | student1 | Test content | Test content 71 description  | module     |
      | OS1        | student1 | Test content | Test content 72 description  | module     |
      | OS1        | student1 | Test content | Test content 73 description  | module     |
      | OS1        | student1 | Test content | Test content 74 description  | module     |
      | OS1        | student1 | Test content | Test content 75 description  | module     |
      | OS1        | student1 | Test content | Test content 76 description  | module     |
      | OS1        | student1 | Test content | Test content 77 description  | module     |
      | OS1        | student1 | Test content | Test content 78 description  | module     |
      | OS1        | student1 | Test content | Test content 79 description  | module     |
      | OS1        | student1 | Test content | Test content 80 description  | module     |
      | OS1        | student1 | Test content | Test content 81 description  | module     |
      | OS1        | student1 | Test content | Test content 82 description  | module     |
      | OS1        | student1 | Test content | Test content 83 description  | module     |
      | OS1        | student1 | Test content | Test content 84 description  | module     |
      | OS1        | student1 | Test content | Test content 85 description  | module     |
      | OS1        | student1 | Test content | Test content 86 description  | module     |
      | OS1        | student1 | Test content | Test content 87 description  | module     |
      | OS1        | student1 | Test content | Test content 88 description  | module     |
      | OS1        | student1 | Test content | Test content 89 description  | module     |
      | OS1        | student1 | Test content | Test content 90 description  | module     |
      | OS1        | student1 | Test content | Test content 91 description  | module     |
      | OS1        | student1 | Test content | Test content 92 description  | module     |
      | OS1        | student1 | Test content | Test content 93 description  | module     |
      | OS1        | student1 | Test content | Test content 94 description  | module     |
      | OS1        | student1 | Test content | Test content 95 description  | module     |
      | OS1        | student1 | Test content | Test content 96 description  | module     |
      | OS1        | student1 | Test content | Test content 97 description  | module     |
      | OS1        | student1 | Test content | Test content 98 description  | module     |
      | OS1        | student1 | Test content | Test content 99 description  | module     |
      | OS1        | student1 | Test content | Test content 100 description | module     |
      | OS1        | student1 | Test content | Test content 101 description | module     |
      | OS1        | student1 | Test content | Test content 102 description | module     |
      | OS1        | student1 | Test content | Test content 103 description | module     |
      | OS1        | student1 | Test content | Test content 104 description | module     |
      | OS1        | student1 | Test content | Test content 105 description | module     |
      | OS1        | student1 | Test content | Test content 106 description | module     |
      | OS1        | student1 | Test content | Test content 107 description | module     |
      | OS1        | student1 | Test content | Test content 108 description | module     |
      | OS1        | student1 | Test content | Test content 109 description | module     |
      | OS1        | student1 | Test content | Test content 110 description | module     |
      | OS1        | student1 | Test content | Test content 111 description | module     |
      | OS1        | student1 | Test content | Test content 112 description | module     |
      | OS1        | student1 | Test content | Test content 113 description | module     |
      | OS1        | student1 | Test content | Test content 114 description | module     |
      | OS1        | student1 | Test content | Test content 115 description | module     |
      | OS1        | student1 | Test content | Test content 116 description | module     |
      | OS1        | student1 | Test content | Test content 117 description | module     |
      | OS1        | student1 | Test content | Test content 118 description | module     |
      | OS1        | student1 | Test content | Test content 119 description | module     |
      | OS1        | student1 | Test content | Test content 120 description | module     |
      | OS1        | student1 | Test content | Test content 121 description | module     |
      | OS1        | student1 | Test content | Test content 122 description | module     |
      | OS1        | student1 | Test content | Test content 123 description | module     |
      | OS1        | student1 | Test content | Test content 124 description | module     |
      | OS1        | student1 | Test content | Test content 125 description | module     |
      | OS1        | student1 | Test content | Test content 126 description | module     |
      | OS1        | student1 | Test content | Test content 127 description | module     |
      | OS1        | student1 | Test content | Test content 128 description | module     |
      | OS1        | student1 | Test content | Test content 129 description | module     |
      | OS1        | student1 | Test content | Test content 130 description | module     |
      | OS1        | student1 | Test content | Test content 131 description | module     |
      | OS1        | student1 | Test content | Test content 132 description | module     |
      | OS1        | student1 | Test content | Test content 133 description | module     |
      | OS1        | student1 | Test content | Test content 134 description | module     |
      | OS1        | student1 | Test content | Test content 135 description | module     |
      | OS1        | student1 | Test content | Test content 136 description | module     |
      | OS1        | student1 | Test content | Test content 137 description | module     |
      | OS1        | student1 | Test content | Test content 138 description | module     |
      | OS1        | student1 | Test content | Test content 139 description | module     |
      | OS1        | student1 | Test content | Test content 140 description | module     |
      | OS1        | student1 | Test content | Test content 141 description | module     |
      | OS1        | student1 | Test content | Test content 142 description | module     |
      | OS1        | student1 | Test content | Test content 143 description | module     |
      | OS1        | student1 | Test content | Test content 144 description | module     |
      | OS1        | student1 | Test content | Test content 145 description | module     |
      | OS1        | student1 | Test content | Test content 146 description | module     |
      | OS1        | student1 | Test content | Test content 147 description | module     |
      | OS1        | student1 | Test content | Test content 148 description | module     |
      | OS1        | student1 | Test content | Test content 149 description | module     |
      | OS1        | student1 | Test content | Test content 150 description | module     |
      | OS1        | student1 | Test content | Test content 151 description | module     |
      | OS1        | student1 | Test content | Test content 152 description | module     |
      | OS1        | student1 | Test content | Test content 153 description | module     |
      | OS1        | student1 | Test content | Test content 154 description | module     |
      | OS1        | student1 | Test content | Test content 155 description | module     |
      | OS1        | student1 | Test content | Test content 156 description | module     |
      | OS1        | student1 | Test content | Test content 157 description | module     |
      | OS1        | student1 | Test content | Test content 158 description | module     |
      | OS1        | student1 | Test content | Test content 159 description | module     |
      | OS1        | student1 | Test content | Test content 160 description | module     |
      | OS1        | student1 | Test content | Test content 161 description | module     |
      | OS1        | student1 | Test content | Test content 162 description | module     |
      | OS1        | student1 | Test content | Test content 163 description | module     |
      | OS1        | student1 | Test content | Test content 164 description | module     |
      | OS1        | student1 | Test content | Test content 165 description | module     |
      | OS1        | student1 | Test content | Test content 166 description | module     |
      | OS1        | student1 | Test content | Test content 167 description | module     |
      | OS1        | student1 | Test content | Test content 168 description | module     |
      | OS1        | student1 | Test content | Test content 169 description | module     |
      | OS1        | student1 | Test content | Test content 170 description | module     |
      | OS1        | student1 | Test content | Test content 171 description | module     |
      | OS1        | student1 | Test content | Test content 172 description | module     |
      | OS1        | student1 | Test content | Test content 173 description | module     |
      | OS1        | student1 | Test content | Test content 174 description | module     |
      | OS1        | student1 | Test content | Test content 175 description | module     |
      | OS1        | student1 | Test content | Test content 176 description | module     |
      | OS1        | student1 | Test content | Test content 177 description | module     |
      | OS1        | student1 | Test content | Test content 178 description | module     |
      | OS1        | student1 | Test content | Test content 179 description | module     |
      | OS1        | student1 | Test content | Test content 180 description | module     |
      | OS1        | student1 | Test content | Test content 181 description | module     |
      | OS1        | student1 | Test content | Test content 182 description | module     |
      | OS1        | student1 | Test content | Test content 183 description | module     |
      | OS1        | student1 | Test content | Test content 184 description | module     |
      | OS1        | student1 | Test content | Test content 185 description | module     |
      | OS1        | student1 | Test content | Test content 186 description | module     |
      | OS1        | student1 | Test content | Test content 187 description | module     |
      | OS1        | student1 | Test content | Test content 188 description | module     |
      | OS1        | student1 | Test content | Test content 189 description | module     |
      | OS1        | student1 | Test content | Test content 190 description | module     |
      | OS1        | student1 | Test content | Test content 191 description | module     |
      | OS1        | student1 | Test content | Test content 192 description | module     |
      | OS1        | student1 | Test content | Test content 193 description | module     |
      | OS1        | student1 | Test content | Test content 194 description | module     |
      | OS1        | student1 | Test content | Test content 195 description | module     |
      | OS1        | student1 | Test content | Test content 196 description | module     |
      | OS1        | student1 | Test content | Test content 197 description | module     |
      | OS1        | student1 | Test content | Test content 198 description | module     |
      | OS1        | student1 | Test content | Test content 199 description | module     |
      | OS1        | student1 | Test content | Test content 200 description | module     |
      | OS1        | student1 | Test content | Test content 201 description | module     |
      | OS1        | student1 | Test content | Test content 202 description | module     |
      | OS1        | student1 | Test content | Test content 203 description | module     |
      | OS1        | student1 | Test content | Test content 204 description | module     |
      | OS1        | student1 | Test content | Test content 205 description | module     |
      | OS1        | student1 | Test content | Test content 206 description | module     |
      | OS1        | student1 | Test content | Test content 207 description | module     |
      | OS1        | student1 | Test content | Test content 208 description | module     |
      | OS1        | student1 | Test content | Test content 209 description | module     |
      | OS1        | student1 | Test content | Test content 210 description | module     |
      | OS1        | student1 | Test content | Test content 211 description | module     |
      | OS1        | student1 | Test content | Test content 212 description | module     |
      | OS1        | student1 | Test content | Test content 213 description | module     |
      | OS1        | student1 | Test content | Test content 214 description | module     |
      | OS1        | student1 | Test content | Test content 215 description | module     |
      | OS1        | student1 | Test content | Test content 216 description | module     |
      | OS1        | student1 | Test content | Test content 217 description | module     |
      | OS1        | student1 | Test content | Test content 218 description | module     |
      | OS1        | student1 | Test content | Test content 219 description | module     |
      | OS1        | student1 | Test content | Test content 220 description | module     |
      | OS1        | student1 | Test content | Test content 221 description | module     |
      | OS1        | student1 | Test content | Test content 222 description | module     |
      | OS1        | student1 | Test content | Test content 223 description | module     |
      | OS1        | student1 | Test content | Test content 224 description | module     |
      | OS1        | student1 | Test content | Test content 225 description | module     |
      | OS1        | student1 | Test content | Test content 226 description | module     |
      | OS1        | student1 | Test content | Test content 227 description | module     |
      | OS1        | student1 | Test content | Test content 228 description | module     |
      | OS1        | student1 | Test content | Test content 229 description | module     |
      | OS1        | student1 | Test content | Test content 230 description | module     |
      | OS1        | student1 | Test content | Test content 231 description | module     |
      | OS1        | student1 | Test content | Test content 232 description | module     |
      | OS1        | student1 | Test content | Test content 233 description | module     |
      | OS1        | student1 | Test content | Test content 234 description | module     |
      | OS1        | student1 | Test content | Test content 235 description | module     |
      | OS1        | student1 | Test content | Test content 236 description | module     |
      | OS1        | student1 | Test content | Test content 237 description | module     |
      | OS1        | student1 | Test content | Test content 238 description | module     |
      | OS1        | student1 | Test content | Test content 239 description | module     |
      | OS1        | student1 | Test content | Test content 240 description | module     |
      | OS1        | student1 | Test content | Test content 241 description | module     |
      | OS1        | student1 | Test content | Test content 242 description | module     |
      | OS1        | student1 | Test content | Test content 243 description | module     |
      | OS1        | student1 | Test content | Test content 244 description | module     |
      | OS1        | student1 | Test content | Test content 245 description | module     |
      | OS1        | student1 | Test content | Test content 246 description | module     |
      | OS1        | student1 | Test content | Test content 247 description | module     |
      | OS1        | student1 | Test content | Test content 248 description | module     |
      | OS1        | student1 | Test content | Test content 249 description | module     |
      | OS1        | student1 | Test content | Test content 250 description | module     |
      | OS1        | student1 | Test content | Test content 251 description | module     |
      | OS1        | student1 | Test content | Test content 252 description | module     |
      | OS1        | student1 | Test content | Test content 253 description | module     |
      | OS1        | student1 | Test content | Test content 254 description | module     |
      | OS1        | student1 | Test content | Test content 255 description | module     |
      | OS1        | student1 | Test content | Test content 256 description | module     |
      | OS1        | student1 | Test content | Test content 257 description | module     |
      | OS1        | student1 | Test content | Test content 258 description | module     |
      | OS1        | student1 | Test content | Test content 259 description | module     |
      | OS1        | student1 | Test content | Test content 260 description | module     |
      | OS1        | student1 | Test content | Test content 261 description | module     |
      | OS1        | student1 | Test content | Test content 262 description | module     |
      | OS1        | student1 | Test content | Test content 263 description | module     |
      | OS1        | student1 | Test content | Test content 264 description | module     |
      | OS1        | student1 | Test content | Test content 265 description | module     |
      | OS1        | student1 | Test content | Test content 266 description | module     |
      | OS1        | student1 | Test content | Test content 267 description | module     |
      | OS1        | student1 | Test content | Test content 268 description | module     |
      | OS1        | student1 | Test content | Test content 269 description | module     |
      | OS1        | student1 | Test content | Test content 270 description | module     |
      | OS1        | student1 | Test content | Test content 271 description | module     |
      | OS1        | student1 | Test content | Test content 272 description | module     |
      | OS1        | student1 | Test content | Test content 273 description | module     |
      | OS1        | student1 | Test content | Test content 274 description | module     |
      | OS1        | student1 | Test content | Test content 275 description | module     |
      | OS1        | student1 | Test content | Test content 276 description | module     |
      | OS1        | student1 | Test content | Test content 277 description | module     |
      | OS1        | student1 | Test content | Test content 278 description | module     |
      | OS1        | student1 | Test content | Test content 279 description | module     |
      | OS1        | student1 | Test content | Test content 280 description | module     |
      | OS1        | student1 | Test content | Test content 281 description | module     |
      | OS1        | student1 | Test content | Test content 282 description | module     |
      | OS1        | student1 | Test content | Test content 283 description | module     |
      | OS1        | student1 | Test content | Test content 284 description | module     |
      | OS1        | student1 | Test content | Test content 285 description | module     |
      | OS1        | student1 | Test content | Test content 286 description | module     |
      | OS1        | student1 | Test content | Test content 287 description | module     |
      | OS1        | student1 | Test content | Test content 288 description | module     |
      | OS1        | student1 | Test content | Test content 289 description | module     |
      | OS1        | student1 | Test content | Test content 290 description | module     |
      | OS1        | student1 | Test content | Test content 291 description | module     |
      | OS1        | student1 | Test content | Test content 292 description | module     |
      | OS1        | student1 | Test content | Test content 293 description | module     |
      | OS1        | student1 | Test content | Test content 294 description | module     |
      | OS1        | student1 | Test content | Test content 295 description | module     |
      | OS1        | student1 | Test content | Test content 296 description | module     |
      | OS1        | student1 | Test content | Test content 297 description | module     |
      | OS1        | student1 | Test content | Test content 298 description | module     |
      | OS1        | student1 | Test content | Test content 299 description | module     |
      | OS1        | student1 | Test content | Test content 300 description | module     |
      | OS1        | student1 | Test content | Test content 301 description | module     |
      | OS1        | student1 | Test content | Test content 302 description | module     |
      | OS1        | student1 | Test content | Test content 303 description | module     |
      | OS1        | student1 | Test content | Test content 304 description | module     |
      | OS1        | student1 | Test content | Test content 305 description | module     |
      | OS1        | student1 | Test content | Test content 306 description | module     |
      | OS1        | student1 | Test content | Test content 307 description | module     |
      | OS1        | student1 | Test content | Test content 308 description | module     |
      | OS1        | student1 | Test content | Test content 309 description | module     |
      | OS1        | student1 | Test content | Test content 310 description | module     |
      | OS1        | student1 | Test content | Test content 311 description | module     |
      | OS1        | student1 | Test content | Test content 312 description | module     |
      | OS1        | student1 | Test content | Test content 313 description | module     |
      | OS1        | student1 | Test content | Test content 314 description | module     |
      | OS1        | student1 | Test content | Test content 315 description | module     |
      | OS1        | student1 | Test content | Test content 316 description | module     |
      | OS1        | student1 | Test content | Test content 317 description | module     |
      | OS1        | student1 | Test content | Test content 318 description | module     |
      | OS1        | student1 | Test content | Test content 319 description | module     |
      | OS1        | student1 | Test content | Test content 320 description | module     |
      | OS1        | student1 | Test content | Test content 321 description | module     |
      | OS1        | student1 | Test content | Test content 322 description | module     |
      | OS1        | student1 | Test content | Test content 323 description | module     |
      | OS1        | student1 | Test content | Test content 324 description | module     |
      | OS1        | student1 | Test content | Test content 325 description | module     |
      | OS1        | student1 | Test content | Test content 326 description | module     |
      | OS1        | student1 | Test content | Test content 327 description | module     |
      | OS1        | student1 | Test content | Test content 328 description | module     |
      | OS1        | student1 | Test content | Test content 329 description | module     |
      | OS1        | student1 | Test content | Test content 330 description | module     |
      | OS1        | student1 | Test content | Test content 331 description | module     |
      | OS1        | student1 | Test content | Test content 332 description | module     |
      | OS1        | student1 | Test content | Test content 333 description | module     |
      | OS1        | student1 | Test content | Test content 334 description | module     |
      | OS1        | student1 | Test content | Test content 335 description | module     |
      | OS1        | student1 | Test content | Test content 336 description | module     |
      | OS1        | student1 | Test content | Test content 337 description | module     |
      | OS1        | student1 | Test content | Test content 338 description | module     |
      | OS1        | student1 | Test content | Test content 339 description | module     |
      | OS1        | student1 | Test content | Test content 340 description | module     |
      | OS1        | student1 | Test content | Test content 341 description | module     |
      | OS1        | student1 | Test content | Test content 342 description | module     |
      | OS1        | student1 | Test content | Test content 343 description | module     |
      | OS1        | student1 | Test content | Test content 344 description | module     |
      | OS1        | student1 | Test content | Test content 345 description | module     |
      | OS1        | student1 | Test content | Test content 346 description | module     |
      | OS1        | student1 | Test content | Test content 347 description | module     |
      | OS1        | student1 | Test content | Test content 348 description | module     |
      | OS1        | student1 | Test content | Test content 349 description | module     |
      | OS1        | student1 | Test content | Test content 350 description | module     |
      | OS1        | student1 | Test content | Test content 351 description | module     |
      | OS1        | student1 | Test content | Test content 352 description | module     |
      | OS1        | student1 | Test content | Test content 353 description | module     |
      | OS1        | student1 | Test content | Test content 354 description | module     |
      | OS1        | student1 | Test content | Test content 355 description | module     |
      | OS1        | student1 | Test content | Test content 356 description | module     |
      | OS1        | student1 | Test content | Test content 357 description | module     |
      | OS1        | student1 | Test content | Test content 358 description | module     |
      | OS1        | student1 | Test content | Test content 359 description | module     |
      | OS1        | student1 | Test content | Test content 360 description | module     |
      | OS1        | student1 | Test content | Test content 361 description | module     |
      | OS1        | student1 | Test content | Test content 362 description | module     |
      | OS1        | student1 | Test content | Test content 363 description | module     |
      | OS1        | student1 | Test content | Test content 364 description | module     |
      | OS1        | student1 | Test content | Test content 365 description | module     |
      | OS1        | student1 | Test content | Test content 366 description | module     |
      | OS1        | student1 | Test content | Test content 367 description | module     |
      | OS1        | student1 | Test content | Test content 368 description | module     |
      | OS1        | student1 | Test content | Test content 369 description | module     |
      | OS1        | student1 | Test content | Test content 370 description | module     |
      | OS1        | student1 | Test content | Test content 371 description | module     |
      | OS1        | student1 | Test content | Test content 372 description | module     |
      | OS1        | student1 | Test content | Test content 373 description | module     |
      | OS1        | student1 | Test content | Test content 374 description | module     |
      | OS1        | student1 | Test content | Test content 375 description | module     |
      | OS1        | student1 | Test content | Test content 376 description | module     |
      | OS1        | student1 | Test content | Test content 377 description | module     |
      | OS1        | student1 | Test content | Test content 378 description | module     |
      | OS1        | student1 | Test content | Test content 379 description | module     |
      | OS1        | student1 | Test content | Test content 380 description | module     |
      | OS1        | student1 | Test content | Test content 381 description | module     |
      | OS1        | student1 | Test content | Test content 382 description | module     |
      | OS1        | student1 | Test content | Test content 383 description | module     |
      | OS1        | student1 | Test content | Test content 384 description | module     |
      | OS1        | student1 | Test content | Test content 385 description | module     |
      | OS1        | student1 | Test content | Test content 386 description | module     |
      | OS1        | student1 | Test content | Test content 387 description | module     |
      | OS1        | student1 | Test content | Test content 388 description | module     |
      | OS1        | student1 | Test content | Test content 389 description | module     |
      | OS1        | student1 | Test content | Test content 390 description | module     |
      | OS1        | student1 | Test content | Test content 391 description | module     |
      | OS1        | student1 | Test content | Test content 392 description | module     |
      | OS1        | student1 | Test content | Test content 393 description | module     |
      | OS1        | student1 | Test content | Test content 394 description | module     |
      | OS1        | student1 | Test content | Test content 395 description | module     |
      | OS1        | student1 | Test content | Test content 396 description | module     |
      | OS1        | student1 | Test content | Test content 397 description | module     |
      | OS1        | student1 | Test content | Test content 398 description | module     |
      | OS1        | student1 | Test content | Test content 399 description | module     |
      | OS1        | student1 | Test content | Test content 400 description | module     |
      | OS1        | student1 | Test content | Test content 401 description | module     |

    When I reload the page
    And I follow "People" in the openstudio navigation
    And I follow "Shared content > My Module" in the openstudio navigation
    And I set the field "View:" to "50"

    # At the 1 begining page, see from 1 to 6 and last page 9
    And I should see "1" in the ".openstudio-desktop-paging .current-page" "css_element"
    And I should see "2" in the ".openstudio-desktop-paging" "css_element"
    And I should see "3" in the ".openstudio-desktop-paging" "css_element"
    And I should see "4" in the ".openstudio-desktop-paging" "css_element"
    And I should see "5" in the ".openstudio-desktop-paging" "css_element"
    And I should see "6" in the ".openstudio-desktop-paging" "css_element"
    And I should see "..." in the ".openstudio-desktop-paging" "css_element"
    And I should not see "7" in the ".openstudio-desktop-paging" "css_element"
    And I should not see "8" in the ".openstudio-desktop-paging" "css_element"
    And I should see "9" in the ".openstudio-desktop-paging a.last" "css_element"

    # Click page 6, still see first page 1, last page 9
    And I click on "6" "link"

    And I should see "1" in the ".openstudio-desktop-paging a.first" "css_element"
    And I should not see "2" in the ".openstudio-desktop-paging" "css_element"
    And I should not see "3" in the ".openstudio-desktop-paging" "css_element"
    And I should see "4" in the ".openstudio-desktop-paging" "css_element"
    And I should see "5" in the ".openstudio-desktop-paging" "css_element"
    And I should see "6" in the ".openstudio-desktop-paging" "css_element"
    And I should see "7" in the ".openstudio-desktop-paging" "css_element"
    And I should see "8" in the ".openstudio-desktop-paging" "css_element"
    And I should see "9" in the ".openstudio-desktop-paging a.last" "css_element"

    # Click page 7, see first page 1
    And I click on "7" "link"
    And I should see "1" in the ".openstudio-desktop-paging a.first" "css_element"
    And I should see "..." in the ".openstudio-desktop-paging" "css_element"
    And I should not see "2" in the ".openstudio-desktop-paging" "css_element"
    And I should not see "3" in the ".openstudio-desktop-paging" "css_element"
    And I should not see "4" in the ".openstudio-desktop-paging" "css_element"
    And I should see "5" in the ".openstudio-desktop-paging" "css_element"
    And I should see "6" in the ".openstudio-desktop-paging" "css_element"
    And I should see "7" in the ".openstudio-desktop-paging" "css_element"
    And I should see "8" in the ".openstudio-desktop-paging" "css_element"
    And I should see "9" in the ".openstudio-desktop-paging" "css_element"

    # Click page 1, see again from 1 - 6 and 9 page
    And I click on "1" "link" in the ".openstudio-desktop-paging" "css_element"
    And I should not see "7" in the ".openstudio-desktop-paging" "css_element"
    And I should not see "8" in the ".openstudio-desktop-paging" "css_element"

    # Check pagination in mobile
    When I change viewport size to "320x768" without runtime scaling
    And I reload the page
    # In page 1, show next button
    And I should see "Next" in the ".openstudio-mobile-paging-next" "css_element"
    And I click on ".openstudio-mobile-paging-next" "css_element"
    Then I should see "Page 2" in the ".openstudio-mobile-paging-current" "css_element"
    And ".openstudio-mobile-paging-previous" "css_element" should exist
    And ".openstudio-mobile-paging-next" "css_element" should exist

    # In page 2, click page 3
    And I click on ".openstudio-mobile-paging-next" "css_element"
    Then I should see "Page 3" in the ".openstudio-mobile-paging-current" "css_element"
    And ".openstudio-mobile-paging-first" "css_element" should exist

  Scenario: Test Pagination.
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I wait "2" seconds
    And the following open studio "contents" exist:
      | openstudio | user     | name         | description                  | visibility |
      | OS1        | student1 | Test content | Test content 1 description   | module     |
      | OS1        | student1 | Test content | Test content 2 description   | module     |
      | OS1        | student1 | Test content | Test content 3 description   | module     |
      | OS1        | student1 | Test content | Test content 4 description   | module     |
      | OS1        | student1 | Test content | Test content 5 description   | module     |
      | OS1        | student1 | Test content | Test content 6 description   | module     |
      | OS1        | student1 | Test content | Test content 7 description   | module     |
      | OS1        | student1 | Test content | Test content 8 description   | module     |
      | OS1        | student1 | Test content | Test content 9 description   | module     |
      | OS1        | student1 | Test content | Test content 10 description  | module     |
      | OS1        | student1 | Test content | Test content 11 description  | module     |
      | OS1        | student1 | Test content | Test content 12 description  | module     |
      | OS1        | student1 | Test content | Test content 13 description  | module     |
      | OS1        | student1 | Test content | Test content 14 description  | module     |
      | OS1        | student1 | Test content | Test content 15 description  | module     |
      | OS1        | student1 | Test content | Test content 16 description  | module     |
      | OS1        | student1 | Test content | Test content 17 description  | module     |
      | OS1        | student1 | Test content | Test content 18 description  | module     |
      | OS1        | student1 | Test content | Test content 19 description  | module     |
      | OS1        | student1 | Test content | Test content 20 description  | module     |
      | OS1        | student1 | Test content | Test content 21 description  | module     |
      | OS1        | student1 | Test content | Test content 22 description  | module     |
      | OS1        | student1 | Test content | Test content 23 description  | module     |
      | OS1        | student1 | Test content | Test content 24 description  | module     |
      | OS1        | student1 | Test content | Test content 25 description  | module     |
      | OS1        | student1 | Test content | Test content 26 description  | module     |
      | OS1        | student1 | Test content | Test content 27 description  | module     |
      | OS1        | student1 | Test content | Test content 28 description  | module     |
      | OS1        | student1 | Test content | Test content 29 description  | module     |
      | OS1        | student1 | Test content | Test content 30 description  | module     |
      | OS1        | student1 | Test content | Test content 31 description  | module     |
      | OS1        | student1 | Test content | Test content 32 description  | module     |
      | OS1        | student1 | Test content | Test content 33 description  | module     |
      | OS1        | student1 | Test content | Test content 34 description  | module     |
      | OS1        | student1 | Test content | Test content 35 description  | module     |
      | OS1        | student1 | Test content | Test content 36 description  | module     |
      | OS1        | student1 | Test content | Test content 37 description  | module     |
      | OS1        | student1 | Test content | Test content 38 description  | module     |
      | OS1        | student1 | Test content | Test content 39 description  | module     |
      | OS1        | student1 | Test content | Test content 40 description  | module     |
      | OS1        | student1 | Test content | Test content 41 description  | module     |
      | OS1        | student1 | Test content | Test content 42 description  | module     |
      | OS1        | student1 | Test content | Test content 43 description  | module     |
      | OS1        | student1 | Test content | Test content 44 description  | module     |
      | OS1        | student1 | Test content | Test content 45 description  | module     |
      | OS1        | student1 | Test content | Test content 46 description  | module     |
      | OS1        | student1 | Test content | Test content 47 description  | module     |
      | OS1        | student1 | Test content | Test content 48 description  | module     |
      | OS1        | student1 | Test content | Test content 49 description  | module     |
      | OS1        | student1 | Test content | Test content 50 description  | module     |
      | OS1        | student1 | Test content | Test content 51 description  | module     |
      | OS1        | student1 | Test content | Test content 52 description  | module     |
      | OS1        | student1 | Test content | Test content 53 description  | module     |
      | OS1        | student1 | Test content | Test content 54 description  | module     |
      | OS1        | student1 | Test content | Test content 55 description  | module     |
      | OS1        | student1 | Test content | Test content 56 description  | module     |
      | OS1        | student1 | Test content | Test content 57 description  | module     |
      | OS1        | student1 | Test content | Test content 58 description  | module     |
      | OS1        | student1 | Test content | Test content 59 description  | module     |
      | OS1        | student1 | Test content | Test content 60 description  | module     |
      | OS1        | student1 | Test content | Test content 61 description  | module     |
      | OS1        | student1 | Test content | Test content 62 description  | module     |
      | OS1        | student1 | Test content | Test content 63 description  | module     |
      | OS1        | student1 | Test content | Test content 64 description  | module     |
      | OS1        | student1 | Test content | Test content 65 description  | module     |
      | OS1        | student1 | Test content | Test content 66 description  | module     |
      | OS1        | student1 | Test content | Test content 67 description  | module     |
      | OS1        | student1 | Test content | Test content 68 description  | module     |
      | OS1        | student1 | Test content | Test content 69 description  | module     |
      | OS1        | student1 | Test content | Test content 70 description  | module     |
      | OS1        | student1 | Test content | Test content 71 description  | module     |
      | OS1        | student1 | Test content | Test content 72 description  | module     |
      | OS1        | student1 | Test content | Test content 73 description  | module     |
      | OS1        | student1 | Test content | Test content 74 description  | module     |
      | OS1        | student1 | Test content | Test content 75 description  | module     |
      | OS1        | student1 | Test content | Test content 76 description  | module     |
      | OS1        | student1 | Test content | Test content 77 description  | module     |
      | OS1        | student1 | Test content | Test content 78 description  | module     |
      | OS1        | student1 | Test content | Test content 79 description  | module     |
      | OS1        | student1 | Test content | Test content 80 description  | module     |
      | OS1        | student1 | Test content | Test content 81 description  | module     |
      | OS1        | student1 | Test content | Test content 82 description  | module     |
      | OS1        | student1 | Test content | Test content 83 description  | module     |
      | OS1        | student1 | Test content | Test content 84 description  | module     |
      | OS1        | student1 | Test content | Test content 85 description  | module     |
      | OS1        | student1 | Test content | Test content 86 description  | module     |
      | OS1        | student1 | Test content | Test content 87 description  | module     |
      | OS1        | student1 | Test content | Test content 88 description  | module     |
      | OS1        | student1 | Test content | Test content 89 description  | module     |
      | OS1        | student1 | Test content | Test content 90 description  | module     |
      | OS1        | student1 | Test content | Test content 91 description  | module     |
      | OS1        | student1 | Test content | Test content 92 description  | module     |
      | OS1        | student1 | Test content | Test content 93 description  | module     |
      | OS1        | student1 | Test content | Test content 94 description  | module     |
      | OS1        | student1 | Test content | Test content 95 description  | module     |
      | OS1        | student1 | Test content | Test content 96 description  | module     |
      | OS1        | student1 | Test content | Test content 97 description  | module     |
      | OS1        | student1 | Test content | Test content 98 description  | module     |
      | OS1        | student1 | Test content | Test content 99 description  | module     |
      | OS1        | student1 | Test content | Test content 100 description | module     |
      | OS1        | student1 | Test content | Test content 101 description | module     |
      | OS1        | student1 | Test content | Test content 102 description | module     |
      | OS1        | student1 | Test content | Test content 103 description | module     |
      | OS1        | student1 | Test content | Test content 104 description | module     |
      | OS1        | student1 | Test content | Test content 105 description | module     |
      | OS1        | student1 | Test content | Test content 106 description | module     |
      | OS1        | student1 | Test content | Test content 107 description | module     |
      | OS1        | student1 | Test content | Test content 108 description | module     |
      | OS1        | student1 | Test content | Test content 109 description | module     |
      | OS1        | student1 | Test content | Test content 110 description | module     |
      | OS1        | student1 | Test content | Test content 111 description | module     |
      | OS1        | student1 | Test content | Test content 112 description | module     |
      | OS1        | student1 | Test content | Test content 113 description | module     |
      | OS1        | student1 | Test content | Test content 114 description | module     |
      | OS1        | student1 | Test content | Test content 115 description | module     |
      | OS1        | student1 | Test content | Test content 116 description | module     |
      | OS1        | student1 | Test content | Test content 117 description | module     |
      | OS1        | student1 | Test content | Test content 118 description | module     |
      | OS1        | student1 | Test content | Test content 119 description | module     |
      | OS1        | student1 | Test content | Test content 120 description | module     |
      | OS1        | student1 | Test content | Test content 121 description | module     |
      | OS1        | student1 | Test content | Test content 122 description | module     |
      | OS1        | student1 | Test content | Test content 123 description | module     |
      | OS1        | student1 | Test content | Test content 124 description | module     |
      | OS1        | student1 | Test content | Test content 125 description | module     |
      | OS1        | student1 | Test content | Test content 126 description | module     |
      | OS1        | student1 | Test content | Test content 127 description | module     |
      | OS1        | student1 | Test content | Test content 128 description | module     |
      | OS1        | student1 | Test content | Test content 129 description | module     |
      | OS1        | student1 | Test content | Test content 130 description | module     |
      | OS1        | student1 | Test content | Test content 131 description | module     |
      | OS1        | student1 | Test content | Test content 132 description | module     |
      | OS1        | student1 | Test content | Test content 133 description | module     |
      | OS1        | student1 | Test content | Test content 134 description | module     |
      | OS1        | student1 | Test content | Test content 135 description | module     |
      | OS1        | student1 | Test content | Test content 136 description | module     |
      | OS1        | student1 | Test content | Test content 137 description | module     |
      | OS1        | student1 | Test content | Test content 138 description | module     |
      | OS1        | student1 | Test content | Test content 139 description | module     |
      | OS1        | student1 | Test content | Test content 140 description | module     |
      | OS1        | student1 | Test content | Test content 141 description | module     |
      | OS1        | student1 | Test content | Test content 142 description | module     |
      | OS1        | student1 | Test content | Test content 143 description | module     |
      | OS1        | student1 | Test content | Test content 144 description | module     |
      | OS1        | student1 | Test content | Test content 145 description | module     |
      | OS1        | student1 | Test content | Test content 146 description | module     |
      | OS1        | student1 | Test content | Test content 147 description | module     |
      | OS1        | student1 | Test content | Test content 148 description | module     |
      | OS1        | student1 | Test content | Test content 149 description | module     |
      | OS1        | student1 | Test content | Test content 150 description | module     |
      | OS1        | student1 | Test content | Test content 151 description | module     |
      | OS1        | student1 | Test content | Test content 152 description | module     |
      | OS1        | student1 | Test content | Test content 153 description | module     |
      | OS1        | student1 | Test content | Test content 154 description | module     |
      | OS1        | student1 | Test content | Test content 155 description | module     |
      | OS1        | student1 | Test content | Test content 156 description | module     |
      | OS1        | student1 | Test content | Test content 157 description | module     |
      | OS1        | student1 | Test content | Test content 158 description | module     |
      | OS1        | student1 | Test content | Test content 159 description | module     |
      | OS1        | student1 | Test content | Test content 160 description | module     |
      | OS1        | student1 | Test content | Test content 161 description | module     |
      | OS1        | student1 | Test content | Test content 162 description | module     |
      | OS1        | student1 | Test content | Test content 163 description | module     |
      | OS1        | student1 | Test content | Test content 164 description | module     |
      | OS1        | student1 | Test content | Test content 165 description | module     |
      | OS1        | student1 | Test content | Test content 166 description | module     |
      | OS1        | student1 | Test content | Test content 167 description | module     |
      | OS1        | student1 | Test content | Test content 168 description | module     |
      | OS1        | student1 | Test content | Test content 169 description | module     |
      | OS1        | student1 | Test content | Test content 170 description | module     |
      | OS1        | student1 | Test content | Test content 171 description | module     |
      | OS1        | student1 | Test content | Test content 172 description | module     |
      | OS1        | student1 | Test content | Test content 173 description | module     |
      | OS1        | student1 | Test content | Test content 174 description | module     |
      | OS1        | student1 | Test content | Test content 175 description | module     |
      | OS1        | student1 | Test content | Test content 176 description | module     |
      | OS1        | student1 | Test content | Test content 177 description | module     |
      | OS1        | student1 | Test content | Test content 178 description | module     |
      | OS1        | student1 | Test content | Test content 179 description | module     |
      | OS1        | student1 | Test content | Test content 180 description | module     |
      | OS1        | student1 | Test content | Test content 181 description | module     |
      | OS1        | student1 | Test content | Test content 182 description | module     |
      | OS1        | student1 | Test content | Test content 183 description | module     |
      | OS1        | student1 | Test content | Test content 184 description | module     |
      | OS1        | student1 | Test content | Test content 185 description | module     |
      | OS1        | student1 | Test content | Test content 186 description | module     |
      | OS1        | student1 | Test content | Test content 187 description | module     |
      | OS1        | student1 | Test content | Test content 188 description | module     |
      | OS1        | student1 | Test content | Test content 189 description | module     |
      | OS1        | student1 | Test content | Test content 190 description | module     |
      | OS1        | student1 | Test content | Test content 191 description | module     |
      | OS1        | student1 | Test content | Test content 192 description | module     |
      | OS1        | student1 | Test content | Test content 193 description | module     |
      | OS1        | student1 | Test content | Test content 194 description | module     |
      | OS1        | student1 | Test content | Test content 195 description | module     |
      | OS1        | student1 | Test content | Test content 196 description | module     |
      | OS1        | student1 | Test content | Test content 197 description | module     |
      | OS1        | student1 | Test content | Test content 198 description | module     |
      | OS1        | student1 | Test content | Test content 199 description | module     |
      | OS1        | student1 | Test content | Test content 200 description | module     |
      | OS1        | student1 | Test content | Test content 201 description | module     |
      | OS1        | student1 | Test content | Test content 202 description | module     |
      | OS1        | student1 | Test content | Test content 203 description | module     |
      | OS1        | student1 | Test content | Test content 204 description | module     |
      | OS1        | student1 | Test content | Test content 205 description | module     |
      | OS1        | student1 | Test content | Test content 206 description | module     |
      | OS1        | student1 | Test content | Test content 207 description | module     |
      | OS1        | student1 | Test content | Test content 208 description | module     |
      | OS1        | student1 | Test content | Test content 209 description | module     |
      | OS1        | student1 | Test content | Test content 210 description | module     |
      | OS1        | student1 | Test content | Test content 211 description | module     |
      | OS1        | student1 | Test content | Test content 212 description | module     |
      | OS1        | student1 | Test content | Test content 213 description | module     |
      | OS1        | student1 | Test content | Test content 214 description | module     |
      | OS1        | student1 | Test content | Test content 215 description | module     |
      | OS1        | student1 | Test content | Test content 216 description | module     |
      | OS1        | student1 | Test content | Test content 217 description | module     |
      | OS1        | student1 | Test content | Test content 218 description | module     |
      | OS1        | student1 | Test content | Test content 219 description | module     |
      | OS1        | student1 | Test content | Test content 220 description | module     |
      | OS1        | student1 | Test content | Test content 221 description | module     |
      | OS1        | student1 | Test content | Test content 222 description | module     |
      | OS1        | student1 | Test content | Test content 223 description | module     |
      | OS1        | student1 | Test content | Test content 224 description | module     |
      | OS1        | student1 | Test content | Test content 225 description | module     |
      | OS1        | student1 | Test content | Test content 226 description | module     |
      | OS1        | student1 | Test content | Test content 227 description | module     |
      | OS1        | student1 | Test content | Test content 228 description | module     |
      | OS1        | student1 | Test content | Test content 229 description | module     |
      | OS1        | student1 | Test content | Test content 230 description | module     |
      | OS1        | student1 | Test content | Test content 231 description | module     |
      | OS1        | student1 | Test content | Test content 232 description | module     |
      | OS1        | student1 | Test content | Test content 233 description | module     |
      | OS1        | student1 | Test content | Test content 234 description | module     |
      | OS1        | student1 | Test content | Test content 235 description | module     |
      | OS1        | student1 | Test content | Test content 236 description | module     |
      | OS1        | student1 | Test content | Test content 237 description | module     |
      | OS1        | student1 | Test content | Test content 238 description | module     |
      | OS1        | student1 | Test content | Test content 239 description | module     |
      | OS1        | student1 | Test content | Test content 240 description | module     |
      | OS1        | student1 | Test content | Test content 241 description | module     |
      | OS1        | student1 | Test content | Test content 242 description | module     |
      | OS1        | student1 | Test content | Test content 243 description | module     |
      | OS1        | student1 | Test content | Test content 244 description | module     |
      | OS1        | student1 | Test content | Test content 245 description | module     |
      | OS1        | student1 | Test content | Test content 246 description | module     |
      | OS1        | student1 | Test content | Test content 247 description | module     |
      | OS1        | student1 | Test content | Test content 248 description | module     |
      | OS1        | student1 | Test content | Test content 249 description | module     |
      | OS1        | student1 | Test content | Test content 250 description | module     |
      | OS1        | student1 | Test content | Test content 251 description | module     |
      | OS1        | student1 | Test content | Test content 252 description | module     |
      | OS1        | student1 | Test content | Test content 253 description | module     |
      | OS1        | student1 | Test content | Test content 254 description | module     |
      | OS1        | student1 | Test content | Test content 255 description | module     |
      | OS1        | student1 | Test content | Test content 256 description | module     |
      | OS1        | student1 | Test content | Test content 257 description | module     |
      | OS1        | student1 | Test content | Test content 258 description | module     |
      | OS1        | student1 | Test content | Test content 259 description | module     |
      | OS1        | student1 | Test content | Test content 260 description | module     |
      | OS1        | student1 | Test content | Test content 261 description | module     |
      | OS1        | student1 | Test content | Test content 262 description | module     |
      | OS1        | student1 | Test content | Test content 263 description | module     |
      | OS1        | student1 | Test content | Test content 264 description | module     |
      | OS1        | student1 | Test content | Test content 265 description | module     |
      | OS1        | student1 | Test content | Test content 266 description | module     |
      | OS1        | student1 | Test content | Test content 267 description | module     |
      | OS1        | student1 | Test content | Test content 268 description | module     |
      | OS1        | student1 | Test content | Test content 269 description | module     |
      | OS1        | student1 | Test content | Test content 270 description | module     |
      | OS1        | student1 | Test content | Test content 271 description | module     |
      | OS1        | student1 | Test content | Test content 272 description | module     |
      | OS1        | student1 | Test content | Test content 273 description | module     |
      | OS1        | student1 | Test content | Test content 274 description | module     |
      | OS1        | student1 | Test content | Test content 275 description | module     |
      | OS1        | student1 | Test content | Test content 276 description | module     |
      | OS1        | student1 | Test content | Test content 277 description | module     |
      | OS1        | student1 | Test content | Test content 278 description | module     |
      | OS1        | student1 | Test content | Test content 279 description | module     |
      | OS1        | student1 | Test content | Test content 280 description | module     |
      | OS1        | student1 | Test content | Test content 281 description | module     |
      | OS1        | student1 | Test content | Test content 282 description | module     |
      | OS1        | student1 | Test content | Test content 283 description | module     |
      | OS1        | student1 | Test content | Test content 284 description | module     |
      | OS1        | student1 | Test content | Test content 285 description | module     |
      | OS1        | student1 | Test content | Test content 286 description | module     |
      | OS1        | student1 | Test content | Test content 287 description | module     |
      | OS1        | student1 | Test content | Test content 288 description | module     |
      | OS1        | student1 | Test content | Test content 289 description | module     |
      | OS1        | student1 | Test content | Test content 290 description | module     |
      | OS1        | student1 | Test content | Test content 291 description | module     |
      | OS1        | student1 | Test content | Test content 292 description | module     |
      | OS1        | student1 | Test content | Test content 293 description | module     |
      | OS1        | student1 | Test content | Test content 294 description | module     |
      | OS1        | student1 | Test content | Test content 295 description | module     |
      | OS1        | student1 | Test content | Test content 296 description | module     |
      | OS1        | student1 | Test content | Test content 297 description | module     |
      | OS1        | student1 | Test content | Test content 298 description | module     |
      | OS1        | student1 | Test content | Test content 299 description | module     |
      | OS1        | student1 | Test content | Test content 300 description | module     |
      | OS1        | student1 | Test content | Test content 301 description | module     |
      | OS1        | student1 | Test content | Test content 302 description | module     |
      | OS1        | student1 | Test content | Test content 303 description | module     |
      | OS1        | student1 | Test content | Test content 304 description | module     |
      | OS1        | student1 | Test content | Test content 305 description | module     |
      | OS1        | student1 | Test content | Test content 306 description | module     |
      | OS1        | student1 | Test content | Test content 307 description | module     |
      | OS1        | student1 | Test content | Test content 308 description | module     |
      | OS1        | student1 | Test content | Test content 309 description | module     |
      | OS1        | student1 | Test content | Test content 310 description | module     |
      | OS1        | student1 | Test content | Test content 311 description | module     |
      | OS1        | student1 | Test content | Test content 312 description | module     |
      | OS1        | student1 | Test content | Test content 313 description | module     |
      | OS1        | student1 | Test content | Test content 314 description | module     |
      | OS1        | student1 | Test content | Test content 315 description | module     |
      | OS1        | student1 | Test content | Test content 316 description | module     |
      | OS1        | student1 | Test content | Test content 317 description | module     |
      | OS1        | student1 | Test content | Test content 318 description | module     |
      | OS1        | student1 | Test content | Test content 319 description | module     |
      | OS1        | student1 | Test content | Test content 320 description | module     |
      | OS1        | student1 | Test content | Test content 321 description | module     |
      | OS1        | student1 | Test content | Test content 322 description | module     |
    When I reload the page
    And I follow "People" in the openstudio navigation
    And I follow "Shared content > My Module" in the openstudio navigation

    # At the 1 begining page, see from 1 2 3 and last page 33.
    And I should see "1" in the ".openstudio-desktop-paging .current-page" "css_element"
    And I should see "2" in the ".openstudio-desktop-paging" "css_element"
    And I should see "3" in the ".openstudio-desktop-paging" "css_element"
    And I should see "33" in the ".openstudio-desktop-paging" "css_element"
    # Click page 33, still see first page 1, last page 33.
    And I click on "33" "link"
    And I should see "33" in the ".openstudio-desktop-paging .current-page" "css_element"
    And I should see "28" in the ".openstudio-desktop-paging" "css_element"
    And I should see "29" in the ".openstudio-desktop-paging" "css_element"
    And I should see "30" in the ".openstudio-desktop-paging" "css_element"
    And I should see "31" in the ".openstudio-desktop-paging" "css_element"
    And I should see "32" in the ".openstudio-desktop-paging" "css_element"

    And I set the field "View:" to "50"
    # At the 1 begining page, see from 1 2 3 and last page 7
    And I should see "1" in the ".openstudio-desktop-paging .current-page" "css_element"
    And I should see "2" in the ".openstudio-desktop-paging" "css_element"
    And I should see "3" in the ".openstudio-desktop-paging" "css_element"
    And I should see "7" in the ".openstudio-desktop-paging" "css_element"
    # Click page 7, still see first page 1, last page 9
    And I click on "7" "link"
    And I should see "7" in the ".openstudio-desktop-paging .current-page" "css_element"
    And I should see "2" in the ".openstudio-desktop-paging" "css_element"
    And I should see "3" in the ".openstudio-desktop-paging" "css_element"
    And I should see "4" in the ".openstudio-desktop-paging" "css_element"
    And I should see "5" in the ".openstudio-desktop-paging" "css_element"
    And I should see "6" in the ".openstudio-desktop-paging" "css_element"

  Scenario: Test Pagination numbers in people page
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I wait "2" seconds
    And the following open studio "contents" exist:
      | openstudio | user      | name         | description                 | visibility |
      | OS1        | student1  | Test content | Test content 1 description  | module     |
      | OS1        | student2  | Test content | Test content 2 description  | module     |
      | OS1        | student3  | Test content | Test content 3 description  | module     |
      | OS1        | student4  | Test content | Test content 4 description  | module     |
      | OS1        | student5  | Test content | Test content 5 description  | module     |
      | OS1        | student6  | Test content | Test content 6 description  | module     |
      | OS1        | student7  | Test content | Test content 7 description  | module     |
      | OS1        | student8  | Test content | Test content 8 description  | module     |
      | OS1        | student9  | Test content | Test content 9 description  | module     |
      | OS1        | student10 | Test content | Test content 10 description | module     |
      | OS1        | student11 | Test content | Test content 11 description | module     |
      | OS1        | student12 | Test content | Test content 12 description | module     |
      | OS1        | student13 | Test content | Test content 13 description | module     |
      | OS1        | student14 | Test content | Test content 14 description | module     |
      | OS1        | student15 | Test content | Test content 15 description | module     |
      | OS1        | student16 | Test content | Test content 16 description | module     |
      | OS1        | student17 | Test content | Test content 17 description | module     |
      | OS1        | student18 | Test content | Test content 18 description | module     |
      | OS1        | student19 | Test content | Test content 19 description | module     |
      | OS1        | student20 | Test content | Test content 20 description | module     |
      | OS1        | student21 | Test content | Test content 21 description | module     |
      | OS1        | student22 | Test content | Test content 22 description | module     |
      | OS1        | student23 | Test content | Test content 23 description | module     |
      | OS1        | student24 | Test content | Test content 24 description | module     |
      | OS1        | student25 | Test content | Test content 25 description | module     |
      | OS1        | student26 | Test content | Test content 26 description | module     |
      | OS1        | student27 | Test content | Test content 27 description | module     |
      | OS1        | student28 | Test content | Test content 28 description | module     |
      | OS1        | student29 | Test content | Test content 29 description | module     |
      | OS1        | student30 | Test content | Test content 30 description | module     |
      | OS1        | student31 | Test content | Test content 31 description | module     |
      | OS1        | student32 | Test content | Test content 32 description | module     |
      | OS1        | student33 | Test content | Test content 33 description | module     |
      | OS1        | student34 | Test content | Test content 34 description | module     |
      | OS1        | student35 | Test content | Test content 35 description | module     |
      | OS1        | student36 | Test content | Test content 36 description | module     |
      | OS1        | student37 | Test content | Test content 37 description | module     |
      | OS1        | student38 | Test content | Test content 38 description | module     |
      | OS1        | student39 | Test content | Test content 39 description | module     |
      | OS1        | student40 | Test content | Test content 40 description | module     |
      | OS1        | student41 | Test content | Test content 41 description | module     |
      | OS1        | student42 | Test content | Test content 42 description | module     |
      | OS1        | student43 | Test content | Test content 43 description | module     |
      | OS1        | student44 | Test content | Test content 44 description | module     |
      | OS1        | student45 | Test content | Test content 45 description | module     |
      | OS1        | student46 | Test content | Test content 46 description | module     |
      | OS1        | student47 | Test content | Test content 47 description | module     |
      | OS1        | student48 | Test content | Test content 48 description | module     |
      | OS1        | student49 | Test content | Test content 49 description | module     |
      | OS1        | student50 | Test content | Test content 50 description | module     |
      | OS1        | student51 | Test content | Test content 51 description | module     |
      | OS1        | student52 | Test content | Test content 52 description | module     |
      | OS1        | student53 | Test content | Test content 53 description | module     |
    When I reload the page
    And I follow "People" in the openstudio navigation
    # At the 1 begining page, see from 1 to 6 and last page 9
    And I should see "1" in the ".openstudio-desktop-paging .current-page" "css_element"
    And I should see "2" in the ".openstudio-desktop-paging" "css_element"
    # Check pagination in mobile
    When I change viewport size to "320x768" without runtime scaling
    And I reload the page
    # In page 1, show next button
    And I should see "Next" in the ".openstudio-mobile-paging-next" "css_element"
    And I click on ".openstudio-mobile-paging-next" "css_element"
    Then I should see "Page 2" in the ".openstudio-mobile-paging-current" "css_element"
    And ".openstudio-mobile-paging-previous" "css_element" should exist

  Scenario: Check "View" on page filter options.
    Given I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Flags | Favourite, Made me laugh |
    And I press "Save and display"
    And the following open studio "contents" exist:
      | openstudio | user     | name         | description                | visibility |
      | OS1        | teacher1 | TestContent1 | Test content 1 description | module     |
    When I follow "Shared content > My Module" in the openstudio navigation
    Then "#filter_pagesize option[value='10']" "css_element" should exist
    And "#filter_pagesize option[value='30']" "css_element" should exist
    And I follow "People" in the openstudio navigation
    And "#filter_pagesize option[value='10']" "css_element" should exist
    And "#filter_pagesize option[value='30']" "css_element" should exist

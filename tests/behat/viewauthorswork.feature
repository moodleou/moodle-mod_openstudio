@ou @ou_vle @mod @mod_openstudio
Feature: View author's work
    When using Open Studio with other users
    As a student
    I need to see other people's work

    Background: Setup course and studio
        Given the following "users" exist:
            | username | firstname | lastname | email |

            # Tutor
            | teacher1 | Teacher | 1 | teacher1@asd.com |

            # Students
            | student1 | Student | 1 | student1@asd.com |
            | student2 | Student | 2 | student2@asd.com |
            | student3 | Student | 3 | student3@asd.com |

        And the following "courses" exist:
            | fullname | shortname | category |
            | Course 1 | C1        | 0        |

        And the following "course enrolments" exist:
            | user | course | role           |
            | teacher1 | C1 | editingteacher |
            | student1 | C1 | student        |
            | student2 | C1 | student        |
            | student3 | C1 | student        |

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
            | student3 | G1 |
            | teacher1 | G2 |
            | student2 | G2 |

        And I log in as "teacher1"
        And I am on site homepage
        And I follow "Course 1"
        And I turn editing mode on
        And I add a "OpenStudio 2 (pilot only)" to section "1" and I fill the form with:
            | Name                         | Test Open Studio name 1      |
            | Description                  | Test Open Studio description |
            | Group mode                   | Visible groups               |
            | Grouping                     | grouping1                    |
            | Teacher                      | true                         |
            | Enable pinboard              | 99                           |
            | Abuse reports are emailed to | teacher1@asd.com             |
            | ID number                    | OS1                          |
        And Open Studio test instance is configured for "Test Open Studio name 1"
        And all users have accepted the plagarism statement for "OS1" openstudio

        # Upload contents
        And the following open studio "contents" exist:
            | openstudio | user     | name                  | description| visibility |
            | OS1        | student1 | Content 1 - onlyme    | name       | private    |
            | OS1        | student1 | Content 1 - my module | name       | module     |
            | OS1        | student1 | Content 1 - tutor     | name       | tutor      |

            | OS1        | student2 | Content 2 - onlyme    | name       | private    |
            | OS1        | student2 | Content 2 - my module | name       | module     |
            | OS1        | student2 | Content 2 - tutor     | name       | tutor      |

            | OS1        | student3 | Content 3 - onlyme    | name       | private    |
            | OS1        | student3 | Content 3 - my module | name       | module     |
            | OS1        | student3 | Content 3 - tutor     | name       | tutor      |

        And the following open studio "contents" exist:
            | openstudio | name            | description       | contenttype | user     | visibilitygroup |
            | OS1        | Student1_group1 | Lorem ipsum dolor | text        | student1 | G1          |
            | OS1        | Student2_group2 | Lorem ipsum dolor | text        | student2 | G2          |
            | OS1        | Student3_group3 | Lorem ipsum dolor | text        | student3 | G1          |
        And I am on site homepage
        And I log out

    @javascript
    Scenario: Test Open Studio with student role
        Given I log in as "student1"
        And I follow "Course 1"
        And I follow "Test Open Studio name 1"
        And I follow "People" in the openstudio navigation
        And I follow "Shared content > My Module" in the openstudio navigation
        And I click on "//h4[contains(text(),'Student 3')]/following-sibling::a" "xpath_element"
        And I wait "1" seconds
        Then I should see "Content 3 - my module"
        Then I should see "Student3_group3"
        Then I should not see "Content 3 - onlyme"
        Then I should not see "Content 3 - tutor"

        And I follow "People" in the openstudio navigation
        And I follow "Shared content > My Module" in the openstudio navigation
        And I click on "//h4[contains(text(),'Student 2')]/following-sibling::a" "xpath_element"
        Then I should see "Content 2 - my module"
        Then I should not see "Student2_group2"
        Then I should not see "Content 2 - onlyme"
        Then I should not see "Content 2 - tutor"
        And I am on site homepage
        And I log out

        # Test Open Studio with teacher role
        Given I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Open Studio name 1"
        And I follow "People" in the openstudio navigation
        And I follow "Shared content > My Module" in the openstudio navigation
        And I click on "//h4[contains(text(),'Student 3')]/following-sibling::a" "xpath_element"
        Then I should see "Content 3 - my module"
        Then I should see "Student3_group3"
        Then I should see "Content 3 - onlyme"
        Then I should see "Content 3 - tutor"

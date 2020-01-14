@ou @ou_vle @mod @mod_openstudio @mod_openstudio_sorting @javascript
Feature: Open Studio preference
  When using Open Studio with other users
  As a teacher
  I need to create a content and upload a file

  Background: Setup course and studio
      Given the following "users" exist:
          | username | firstname | lastname | email |
          | teacher1 | Teacher | 1 | teacher1@asd.com |
          | student1 | Student | 1 | student1@asd.com |
          | student2 | Student | 2 | student2@asd.com |
          | student3 | Student | 3 | student3@asd.com |
          | student4 | Student | 4 | student4@asd.com |
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
      And the following "groupings" exist:
          | name      | course | idnumber |
          | grouping1 | C1     | GI1      |
      And the following "grouping groups" exist:
          | grouping | group |
          | GI1      | G1    |
      And the following "group members" exist:
          | user     | group  |
          | teacher1 | G1 |
          | student1 | G1 |
          | student2 | G1 |
          | student3 | G1 |
      And I log in as "admin"
      And I am on "Course 1" course homepage
      And I turn editing mode on
      And I add a "OpenStudio 2 (pilot only)" to section "1" and I fill the form with:
        | Name                         | Test Open Studio name 1      |
        | Description                  | Test Open Studio description |
        | Group mode                   | Visible groups               |
        | Grouping                     | grouping1                    |
        | Enable pinboard              | 99                           |
        | Enable Folders               | 1                            |
        | Abuse reports are emailed to | teacher1@asd.com             |
        | ID number                    | OS1                          |
      And all users have accepted the plagarism statement for "OS1" openstudio

      And the following open studio "level1s" exist:
          | openstudio  | name         | sortorder |
          | OS1         | Block1       | 1         |
          | OS1         | Block2       | 1         |
      And the following open studio "level2s" exist:
          | level1      | name         | sortorder |
          | Block1      | Activity1    | 1         |
          | Block1      | Activity2    | 1         |
          | Block1      | Activity3    | 1         |
          | Block2      | ActivityB2   | 1         |
      And the following open studio "level3s" exist:
          | level2      | name         | sortorder |
          | Activity1   | Content1.1   | 1         |
          | Activity2   | Content2.1   | 1         |
          | Activity3   | Content3.1   | 1         |
          | ActivityB2  | ContentB2    | 1         |
      And Open Studio levels are configured for "Test Open Studio name 1"

  Scenario: Preferences Feature in My Module:
      Given the following open studio "contents" exist:
          | openstudio | user     | name                        | description                   | file                                              | visibility |
          | OS1        | teacher1 | Test My Preferences View 1  | My Preferences Description 1  | mod/openstudio/tests/importfiles/test1.jpg        | module     |
          | OS1        | student1 | Test My Preferences View 2  | My Preferences Description 2  | mod/openstudio/tests/importfiles/test.mp4         | module     |
          | OS1        | student2 | Test My Preferences View 3  | My Preferences Description 3  | mod/openstudio/tests/importfiles/test.mp3         | module     |

      And the following open studio "level3contents" exist:
          | openstudio | user     | name                        | description                   | weblink                        | visibility | level3       | levelcontainer |
          | OS1        | teacher1 | Test My Preferences View 4  | My Preferences Description 4  | https://www.example.com        | module     | Content2.1   | module         |

      And I am on site homepage
      And I log out
      And I log in as "teacher1"
      And I am on "Course 1" course homepage
      And I follow "Test Open Studio name 1"
      And I follow "People" in the openstudio navigation
      And I follow "Shared content > My Module" in the openstudio navigation
      And I should see "Test My Preferences View 1"
      And I should see "Test My Preferences View 2"
      And I should see "Test My Preferences View 3"
      And I should see "Test My Preferences View 4"

      # Filter by Img
      And I press "Filter"
      And I set the field "Image" to "1"
      And I press "Apply"
      And I should see "Test My Preferences View 1"
      And I should not see "Test My Preferences View 2"
      And I should not see "Test My Preferences View 3"
      And I should not see "Test My Preferences View 4"

      # Filter by Video
      And I set the field "Image" to "0"
      And I set the field "Video" to "1"
      And I press "Apply"
      And I should not see "Test My Preferences View 1"
      And I should see "Test My Preferences View 2"
      And I should not see "Test My Preferences View 3"
      And I should not see "Test My Preferences View 4"

      # Filter by Audio
      And I set the field "Video" to "0"
      And I set the field "Audio" to "1"
      And I press "Apply"
      And I should not see "Test My Preferences View 1"
      And I should not see "Test My Preferences View 2"
      And I should see "Test My Preferences View 3"
      And I should not see "Test My Preferences View 4"

      # Filter by Web link
      And I set the field "Audio" to "0"
      And I set the field "Web link" to "1"
      And I press "Apply"
      And I should not see "Test My Preferences View 1"
      And I should not see "Test My Preferences View 2"
      And I should not see "Test My Preferences View 3"
      And I should see "Test My Preferences View 4"

      # Filter by Not viewed:
      And I click on "select#filter_block" "css_element"
      And I click on "option[name='All']" "css_element"
      And I set the field "All types" to "1"
      And I set the field "Not viewed" to "1"
      And I press "Apply"
      And I should see "Test My Preferences View 1"
      And I should see "Test My Preferences View 2"
      And I should see "Test My Preferences View 3"
      And I should see "Test My Preferences View 4"

      # Filter by Viewed:
      And I set the field "Viewed" to "1"
      And I press "Apply"
      And I should not see "Test My Preferences View 1"
      And I should not see "Test My Preferences View 2"
      And I should not see "Test My Preferences View 3"
      And I should not see "Test My Preferences View 4"

      # Filter by Select from Pinboard:
      And I click on "select#filter_block" "css_element"
      And I click on "option[name='Pinboard']" "css_element"
      And I set the field "All post" to "1"
      And I press "Apply"
      And I should see "Test My Preferences View 1"
      And I should see "Test My Preferences View 2"
      And I should see "Test My Preferences View 3"
      And I should see "Test My Preferences View 4"

      # Filter by Select from Blocks:
      And I set the field "Block1" to "1"
      And I press "Apply"
      And I should see "Test My Preferences View 4"
      And I set the field "Block1" to "0"
      And I set the field "Block2" to "1"
      And I press "Apply"
      And I should not see "Test My Preferences View 4"

      # Press reset button:
      And I press "Reset"
      And I should see "Test My Preferences View 1"
      And I should see "Test My Preferences View 2"
      And I should see "Test My Preferences View 3"
      And I should see "Test My Preferences View 4"

  Scenario: Preferences Feature in My Group:
      Given the following open studio "contents" exist:
        | openstudio | user       | name                       | description                  | file                                       | visibilitygroup |
        | OS1        | teacher1   | Test My Preferences View 1 | My Preferences Description 1 | mod/openstudio/tests/importfiles/test1.jpg | G1              |
        | OS1        | student2   | Test My Preferences View 2 | My Preferences Description 2 | mod/openstudio/tests/importfiles/test.mp4  | G1              |
        | OS1        | student3   | Test My Preferences View 3 | My Preferences Description 3 | mod/openstudio/tests/importfiles/test.mp3  | G1              |

      And the following open studio "level3contents" exist:
        | openstudio | user     | name                        | description                   | weblink                        | visibilitygroup | level3       | levelcontainer |
        | OS1        | teacher1 | Test My Preferences View 4  | My Preferences Description 4  | https://www.example.com        | G1              | Content2.1   | module         |

      And I am on site homepage
      And I log out
      And I log in as "teacher1"
      And I am on "Course 1" course homepage
      And I follow "Test Open Studio name 1"
      And I follow "Shared content > My Group" in the openstudio navigation
      And I should see "Test My Preferences View 1"
      And I should see "Test My Preferences View 2"
      And I should see "Test My Preferences View 3"
      And I should see "Test My Preferences View 4"

      # Filter by Img
      And I press "Filter"
      And I set the field "Image" to "1"
      And I press "Apply"
      And I should see "Test My Preferences View 1"
      And I should not see "Test My Preferences View 2"
      And I should not see "Test My Preferences View 3"
      And I should not see "Test My Preferences View 4"

      # Filter by Video
      And I set the field "Image" to "0"
      And I set the field "Video" to "1"
      And I press "Apply"
      And I should not see "Test My Preferences View 1"
      And I should see "Test My Preferences View 2"
      And I should not see "Test My Preferences View 3"
      And I should not see "Test My Preferences View 4"

      # Filter by Audio
      And I set the field "Video" to "0"
      And I set the field "Audio" to "1"
      And I press "Apply"
      And I should not see "Test My Preferences View 1"
      And I should not see "Test My Preferences View 2"
      And I should see "Test My Preferences View 3"
      And I should not see "Test My Preferences View 4"

      # Filter by Web link
      And I set the field "Audio" to "0"
      And I set the field "Web link" to "1"
      And I press "Apply"
      And I should not see "Test My Preferences View 1"
      And I should not see "Test My Preferences View 2"
      And I should not see "Test My Preferences View 3"
      And I should see "Test My Preferences View 4"

      # Filter by Not viewed:
      And I click on "select#filter_block" "css_element"
      And I click on "option[name='All']" "css_element"
      And I set the field "All types" to "1"
      And I set the field "Not viewed" to "1"
      And I press "Apply"
      And I should see "Test My Preferences View 1"
      And I should see "Test My Preferences View 2"
      And I should see "Test My Preferences View 3"
      And I should see "Test My Preferences View 4"

      # Filter by Viewed:
      And I set the field "Viewed" to "1"
      And I press "Apply"
      And I should not see "Test My Preferences View 1"
      And I should not see "Test My Preferences View 2"
      And I should not see "Test My Preferences View 3"
      And I should not see "Test My Preferences View 4"

      # Filter by Select from Pinboard:
      And I click on "select#filter_block" "css_element"
      And I click on "option[name='Pinboard']" "css_element"
      And I set the field "All post" to "1"
      And I press "Apply"
      And I should see "Test My Preferences View 1"
      And I should see "Test My Preferences View 2"
      And I should see "Test My Preferences View 3"
      And I should see "Test My Preferences View 4"

      # Filter by Select from Blocks:
      And I set the field "Block1" to "1"
      And I press "Apply"
      And I should see "Test My Preferences View 4"
      And I set the field "Block1" to "0"
      And I set the field "Block2" to "1"
      And I press "Apply"
      And I should not see "Test My Preferences View 4"

      # Press reset button:
      And I press "Reset"
      And I should see "Test My Preferences View 1"
      And I should see "Test My Preferences View 2"
      And I should see "Test My Preferences View 3"
      And I should see "Test My Preferences View 4"

  Scenario: Preferences Feature in My Pinboard:
      Given the following open studio "contents" exist:
        | openstudio | user     | name                        | description                   | file                                              | visibility |
        | OS1        | teacher1 | Test My Preferences View 1  | My Preferences Description 1  | mod/openstudio/tests/importfiles/test1.jpg        | private    |
        | OS1        | teacher1 | Test My Preferences View 2  | My Preferences Description 2  | mod/openstudio/tests/importfiles/test.mp4         | private    |
        | OS1        | teacher1 | Test My Preferences View 3  | My Preferences Description 3  | mod/openstudio/tests/importfiles/test.mp3         | private    |

      And the following open studio "level3contents" exist:
        | openstudio | user     | name                        | description                   | weblink                         | visibility | level3       | levelcontainer |
        | OS1        | teacher1 | Test My Preferences View 4  | My Preferences Description 4  | https://www.example.com         | private    | Content2.1   | module         |

      And I am on site homepage
      And I log out
      And I log in as "teacher1"
      And I am on "Course 1" course homepage
      And I follow "Test Open Studio name 1"
      And I follow "My content > My Pinboard" in the openstudio navigation
      And I should see "Test My Preferences View 1"
      And I should see "Test My Preferences View 2"
      And I should see "Test My Preferences View 3"
      And I should not see "Test My Preferences View 4"

      # Filter by Img
      And I press "Filter"
      And I set the field "Image" to "1"
      And I press "Apply"
      And I should see "Test My Preferences View 1"
      And I should not see "Test My Preferences View 2"
      And I should not see "Test My Preferences View 3"
      And I should not see "Test My Preferences View 4"

      # Filter by Video
      And I set the field "Image" to "0"
      And I set the field "Video" to "1"
      And I press "Apply"
      And I should not see "Test My Preferences View 1"
      And I should see "Test My Preferences View 2"
      And I should not see "Test My Preferences View 3"
      And I should not see "Test My Preferences View 4"

      # Filter by Audio
      And I set the field "Video" to "0"
      And I set the field "Audio" to "1"
      And I press "Apply"
      And I should not see "Test My Preferences View 1"
      And I should not see "Test My Preferences View 2"
      And I should see "Test My Preferences View 3"
      And I should not see "Test My Preferences View 4"

      # Filter by Web link
      And I set the field "Audio" to "0"
      And I set the field "Web link" to "1"
      And I press "Apply"
      And I should not see "Test My Preferences View 1"
      And I should not see "Test My Preferences View 2"
      And I should not see "Test My Preferences View 3"
      And I should not see "Test My Preferences View 4"

      # Filter by Not viewed:
      And I set the field "All types" to "1"
      And I set the field "Not viewed" to "1"
      And I press "Apply"
      And I should see "Test My Preferences View 1"
      And I should see "Test My Preferences View 2"
      And I should see "Test My Preferences View 3"

      # Filter by Viewed:
      And I set the field "Viewed" to "1"
      And I press "Apply"
      And I should not see "Test My Preferences View 1"
      And I should not see "Test My Preferences View 2"
      And I should not see "Test My Preferences View 3"

      # Press reset button:
      And I press "Reset"
      And I should see "Test My Preferences View 1"
      And I should see "Test My Preferences View 2"
      And I should see "Test My Preferences View 3"
      And I should not see "Test My Preferences View 4"

  Scenario: Preferences Feature in My Activities:
      Given the following open studio "level3contents" exist:
        | openstudio | user     | name                        | description                   | file                                               | visibility | level3       | levelcontainer |
        | OS1        | teacher1 | Test My Preferences View 1  | My Preferences Description 1  | mod/openstudio/tests/importfiles/test1.jpg         | module     | Content1.1   | module         |
        | OS1        | teacher1 | Test My Preferences View 2  | My Preferences Description 2  | mod/openstudio/tests/importfiles/test.mp4          | module     | Content2.1   | module         |
        | OS1        | teacher1 | Test My Preferences View 3  | My Preferences Description 3  | mod/openstudio/tests/importfiles/test.mp3          | module     | Content3.1   | module         |

      Given the following open studio "level3contents" exist:
        | openstudio | user     | name                        | description                   | weblink                         | visibility | level3       | levelcontainer |
        | OS1        | teacher1 | Test My Preferences View 4  | My Preferences Description 4  | https://www.example.com         | module     | ContentB2    | module         |

      And I am on site homepage
      And I log out
      And I log in as "teacher1"
      And I am on "Course 1" course homepage
      And I follow "Test Open Studio name 1"
      And I follow "My content > My Activities" in the openstudio navigation
      And I should see "Test My Preferences View 1"
      And I should see "Test My Preferences View 2"
      And I should see "Test My Preferences View 3"
      And I should see "Test My Preferences View 4"

      # Filter by Img
      And I press "Filter"
      And I set the field "Image" to "1"
      And I press "Apply"
      And I should see "Test My Preferences View 1"
      And I should not see "Test My Preferences View 2"
      And I should not see "Test My Preferences View 3"
      And I should not see "Test My Preferences View 4"

      # Filter by Video
      And I set the field "Image" to "0"
      And I set the field "Video" to "1"
      And I press "Apply"
      And I should not see "Test My Preferences View 1"
      And I should see "Test My Preferences View 2"
      And I should not see "Test My Preferences View 3"
      And I should not see "Test My Preferences View 4"

      # Filter by Audio
      And I set the field "Video" to "0"
      And I set the field "Audio" to "1"
      And I press "Apply"
      And I should not see "Test My Preferences View 1"
      And I should not see "Test My Preferences View 2"
      And I should see "Test My Preferences View 3"
      And I should not see "Test My Preferences View 4"

      # Filter by Web link
      And I set the field "Audio" to "0"
      And I set the field "Web link" to "1"
      And I press "Apply"
      And I should not see "Test My Preferences View 1"
      And I should not see "Test My Preferences View 2"
      And I should not see "Test My Preferences View 3"
      And I should see "Test My Preferences View 4"

      # Filter by Not viewed:
      And I set the field "All types" to "1"
      And I set the field "Not viewed" to "1"
      And I press "Apply"
      And I should see "Test My Preferences View 1"
      And I should see "Test My Preferences View 2"
      And I should see "Test My Preferences View 3"
      And I should see "Test My Preferences View 4"

      # Filter by Viewed:
      And I set the field "Viewed" to "1"
      And I press "Apply"
      And I should not see "Test My Preferences View 1"
      And I should not see "Test My Preferences View 2"
      And I should not see "Test My Preferences View 3"
      And I should not see "Test My Preferences View 4"

      # Filter by Select from Pinboard:
      And I click on "select#filter_block" "css_element"
      And I set the field "All post" to "1"
      And I press "Apply"
      And I should see "Test My Preferences View 1"
      And I should see "Test My Preferences View 2"
      And I should see "Test My Preferences View 3"
      And I should see "Test My Preferences View 4"

      # Filter by Select from Blocks:
      And I press "Filter"
      And I set the field "Block1" to "1"
      And I press "Apply"
      And I should see "Test My Preferences View 1"
      And I should see "Test My Preferences View 2"
      And I should see "Test My Preferences View 3"
      And I set the field "Block1" to "0"
      And I set the field "Block2" to "1"
      And I press "Apply"
      And I should see "Test My Preferences View 4"

      # Press reset button:
      And I press "Reset"
      And I should see "Test My Preferences View 1"
      And I should see "Test My Preferences View 2"
      And I should see "Test My Preferences View 3"
      And I should see "Test My Preferences View 4"

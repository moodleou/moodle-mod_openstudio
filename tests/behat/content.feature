@ou @ou_vle @mod @mod_openstudio @mod_openstudio_test_content @_file_upload @javascript
Feature: Create Open Studio contents
When using Open Studio with other users
As a teacher
I need to create a content and upload a file

  Background: Setup course and studio
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
      | student2 | Student   | 2        | student2@asd.com |
      | student3 | Student   | 3        | student3@asd.com |
      | student4 | Student   | 4        | student4@asd.com |
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
      | teacher1 | G3    |
      | student3 | G3    |
      | student4 | G3    |
    And the following open studio "instances" exist:
      | course | name                    | description                  | pinboard | idnumber | groupmode | grouping | pinboard | reportingemail   |
      | C1     | Test Open Studio name 1 | Test Open Studio description | 99       | OS1      | 2         | GI1      | 99       | teacher1@asd.com |
    And all users have accepted the plagarism statement for "OS1" openstudio

  Scenario: Add new content just a title and description
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I should see "Test Open Studio name 1"
    And I follow "Add new content"
    And I set the following fields to these values:
      | My Module   | 1                                 |
      | Title       | Test My Group Board View 1        |
      | Description | My Group Board View Description 1 |
    And I press "Add file"
    And I wait "1" seconds
    And I should see "Maximum file size"
    And I press "Add web/embed link"
    And I should not see "Maximum file size"
    And I press "Add web/embed link"
    And I should not see "Details"
    And I press "Save"
    And I follow "Shared content > My Module" in the openstudio navigation
    And I should see "View all work that’s been shared with the module cohort, including your own."
    Then the "src" attribute of "img.openstudio-grid-item-thumbnail" "css_element" should contain "online_rgb_32p"

  Scenario: Add new content just a title and description with a file
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I follow "Add new content"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                          |
      | Title       | Test My Group Board View 2                 |
      | Description | My Group Board View Description 2          |
      | Files       | mod/openstudio/tests/importfiles/test2.jpg |
    And I set the field "Describe this image for someone who cannot see it" to "This is image alt"
    And I press "Save"
    And I follow "Shared content > My Module" in the openstudio navigation
    Then the "src" attribute of "div.openstudio-grid-item-content-preview img" "css_element" should contain "test2.jpg"
    And I should see "Test My Group Board View 2"

  Scenario: Add new content just a title and description with an image including GPS and EXIF data
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I follow "Add new content"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module        | 1                                          |
      | Title            | Test My Group Board View 3                 |
      | Description      | My Group Board View Description 3          |
      | Files            | mod/openstudio/tests/importfiles/test3.jpg |
      | Show GPS data    | 1                                          |
      | Show image data  | 1                                          |
      | Retain EXIF data | 1                                          |
    And I set the field "Describe this image for someone who cannot see it" to "This is image alt"
    And I press "Save"
    And I follow "Shared content > My Module" in the openstudio navigation
    Then the "src" attribute of "div.openstudio-grid-item-content-preview img" "css_element" should contain "test3.jpg"
    And I should see "Test My Group Board View 3"

  Scenario: Add new content just a title and description with a file, ownership data
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I follow "Add new content"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                                    |
      | Title       | Test My Group Board View 4 ownership All my own work |
      | Description | My Group Board View Description 4 ownership          |
      | Files       | mod/openstudio/tests/importfiles/test3.jpg           |
    Then "//input[@id='id_ownershipdetail' and @disabled='disabled']" "xpath_element" should exist
    And I set the field "Describe this image for someone who cannot see it" to "This is image alt"
    And I press "Save"
    And I follow "Shared content > My Module" in the openstudio navigation
    Then the "src" attribute of "div.openstudio-grid-item-content-preview img" "css_element" should contain "test3.jpg"
    And I should see "Test My Group Board View 4 ownership All my own work"

    And I follow "Add new content"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module       | 1                                                    |
      | Title           | Test My Group Board View 4 ownership Found elsewhere |
      | Description     | My Group Board View Description 3 ownership          |
      | Files           | mod/openstudio/tests/importfiles/test3.jpg           |
      | Found elsewhere | 1                                                    |
      | Details         | Test 4                                               |
    And I set the field "Describe this image for someone who cannot see it" to "This is image alt"
    And I press "Save"
    And I follow "Shared content > My Module" in the openstudio navigation
    Then the "src" attribute of "div.openstudio-grid-item-content-preview img" "css_element" should contain "test3.jpg"
    And I should see "Test My Group Board View 4 ownership Found elsewhere"

  Scenario: Add new content just a title and description with a file and tags
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I follow "Add new content"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                          |
      | Title       | Test My Group Board View 4 Tags            |
      | Description | My Group Board View Description 4 Tags     |
      | Files       | mod/openstudio/tests/importfiles/test4.jpg |
      | Tags        | Tests Add New Tags                         |
    And I wait "2" seconds
    And I should see "Tests Add New Tags"
    And I set the field "Describe this image for someone who cannot see it" to "This is image alt"
    And I press "Save"
    And I follow "Shared content > My Module" in the openstudio navigation
    Then the "src" attribute of "div.openstudio-grid-item-content-preview img" "css_element" should contain "test4.jpg"
    And I should see "Test My Group Board View 4 Tags"

  Scenario: Add new content just a title and description with a weblink
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I follow "Add new content"
    And I press "Add web/embed link"
    And I set the following fields to these values:
      | My Module   | 1                                                    |
      | Title       | Test My Group Board View 5 Add web/embed link        |
      | Description | My Group Board View Description 5 Add web/embed link |
      | Web link    | https://www.youtube.com/watch?v=ktAnpf_nu5c          |
    And I press "Save"
    And I follow "Shared content > My Module" in the openstudio navigation
    Then I should see "Test My Group Board View 5 Add web/embed link"

  Scenario: Add new content just a title and description with a weblink, ownership data
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I follow "Add new content"
    And I press "Add web/embed link"
    And I set the following fields to these values:
      | My Module   | 1                                                    |
      | Title       | Test My Group Board View 5 ownership All my own work |
      | Description | My Group Board View Description 5 ownership          |
      | Web link    | https://www.youtube.com/watch?v=Y7uGHY-t80I          |
    Then "//input[@id='id_ownershipdetail' and @disabled='disabled']" "xpath_element" should exist
    And I press "Save"
    And I follow "Shared content > My Module" in the openstudio navigation
    Then I should see "Test My Group Board View 5 ownership All my own work"

    And I follow "Add new content"
    And I press "Add web/embed link"
    And I set the following fields to these values:
      | My Module       | 1                                                    |
      | Title           | Test My Group Board View 5 ownership Found elsewhere |
      | Description     | My Group Board View Description 5 ownership          |
      | Web link        | https://www.youtube.com/watch?v=BGD6L-4yceY          |
      | Found elsewhere | 1                                                    |
      | Details         | Test 5                                               |
    And I press "Save"
    And I follow "Shared content > My Module" in the openstudio navigation
    Then I should see "Test My Group Board View 5 ownership Found elsewhere"

  Scenario: Add new content just a title and description with a weblink and tags
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I follow "Add new content"
    And I press "Add web/embed link"
    And I set the following fields to these values:
      | My Module   | 1                                           |
      | Title       | Test My Group Board View 5 Tags             |
      | Description | My Group Board View Description 5 Tags      |
      | Web link    | https://www.youtube.com/watch?v=qyId4XZdC_4 |
      | Tags        | Tests Add New Tags web/embed link           |
    And I wait "2" seconds
    And I should see "Tests Add New Tags web/embed link"
    And I press "Save"
    And I follow "Shared content > My Module" in the openstudio navigation
    Then I should see "Test My Group Board View 5 Tags"

  Scenario: Add new content just a title and description with a txt file
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I follow "Add new content"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                         |
      | Title       | Test My Group Board View 6 Tags           |
      | Description | My Group Board View Description 6 Tags    |
      | Files       | mod/openstudio/tests/importfiles/test.txt |
      | Tags        | Tests Add New Tags                        |
    And I wait "2" seconds
    And I should see "Tests Add New Tags"
    And I press "Save"
    And I follow "Shared content > My Module" in the openstudio navigation
    Then I should see "Test My Group Board View 6 Tags"

  Scenario: Add new content just a title and description with a m4v file
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I follow "Add new content"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                         |
      | Title       | Test My Group Board View 7 Tags           |
      | Description | My Group Board View Description 7 Tags    |
      | Files       | mod/openstudio/tests/importfiles/test.m4v |
      | Tags        | Tests Add New Tags add file m4v           |
    And I wait "2" seconds
    And I should see "Tests Add New Tags add file m4v"
    And I press "Save"
    And I follow "Shared content > My Module" in the openstudio navigation
    And I should see "Test My Group Board View 7 Tags"
    And I click on "//div[@class='openstudio-grid-item-content-box'][1]//a" "xpath_element"
    And I should see "Tests Add New Tags add file m4v"
    Then the "href" attribute of "div.openstudio-content-view-primary a" "css_element" should contain "test.m4v"
    And I should see "Test My Group Board View 7 Tags"

  Scenario: Add new content just a title and description with a m4a file
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I follow "Add new content"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                         |
      | Title       | Test My Group Board View 8 Tags           |
      | Description | My Group Board View Description 8 Tags    |
      | Files       | mod/openstudio/tests/importfiles/test.m4a |
      | Tags        | Tests Add New Tags add file m4a           |
    And I wait "2" seconds
    And I should see "Tests Add New Tags add file m4a"
    And I press "Save"
    And I follow "Shared content > My Module" in the openstudio navigation
    And I should see "Test My Group Board View 8 Tags"
    And I click on "//div[@class='openstudio-grid-item-content-box'][1]//a" "xpath_element"
    And I should see "Tests Add New Tags add file m4a"
    Then the "href" attribute of "div.openstudio-content-view-primary a" "css_element" should contain "test.m4a"
    And I should see "Test My Group Board View 8 Tags"

  Scenario: Add new content just a title and description with a nbk file
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I follow "Add new content"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                           |
      | Title       | My Module Title                             |
      | Description | My Module Description                       |
      | Files       | mod/openstudio/tests/importfiles/3files.nbk |
    And I press "Save"
    When I switch to "openstudio-content-view-iframe" iframe
    Then I should see "Anscombe's Quartet" in the "#notebook-container .text_cell_render h1" "css_element"

  Scenario: Add new content just a title and description with a zip file
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I follow "Add new content"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                         |
      | Title       | My Module Title                           |
      | Description | My Module Description                     |
      | Files       | mod/openstudio/tests/importfiles/test.zip |
    And I press "Save"
    Then the "src" attribute of "img.openstudio-content-view-type-img" "css_element" should contain "zip_archive"
    And I should see "Download file attachment"
    And I follow "Download file attachment"

  Scenario: View activity by other user
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I navigate to "Manage levels" in current page administration
    And I press "Add another Block"
    And I set the field "Block Name" to "Block 1"
    And I press "Save Changes"
    And I follow "Block 1"
    And I press "Add another Activity"
    And I set the field "Activity Name" to "Activity 1"
    And I press "Save Changes"
    And I press "Add another Activity"
    And I set the field "Activity Name" to "Activity 2"
    And I press "Save Changes"
    And I press "Add another Activity"
    And I set the field "Activity Name" to "Activity 3"
    And I press "Save Changes"
    And I follow "Activity 1"
    And I press "Add another Content"
    And I set the field "Content Name" to "Content 1.1 Required"
    And I set the field "Required (TMA)" to "1"
    And I press "Add another Content"
    And I follow "Manage levels"
    And I follow "Block 1"
    And I follow "Activity 3"
    And I press "Add another Content"
    And I set the field "Content Name" to "Content 3.1"
    And I press "Add another Content"
    And I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "Add new content"
    And I set the following fields to these values:
      | My Module   | 1                                 |
      | Title       | Test My Group Board View 1        |
      | Description | My Group Board View Description 1 |
    And I press "Save"
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "student2"
    And I should see "View all"
    And I click on ".openstudio-grid-item-content-detail-owner-view" "css_element"
    And I follow "My Content > My Activities" in the openstudio navigation
    Then I should see "Content 1.1 Required"

  Scenario: Add new content just a title and description and link
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I follow "Add new content"
    And I press "Add web/embed link"
    And I set the following fields to these values:
      | My Module   | 1                                                         |
      | Title       | Test Content with iplayer web/embed link                  |
      | Description | Test Content with iplayer web/embed link                  |
      | Web link    | https://www.bbc.co.uk/iplayer/episode/m000pb58/perfect-10 |
    And I press "Save"
    And I follow "Shared content > My Module" in the openstudio navigation
    And I follow "Test Content with iplayer web/embed link"
    Then I should see "This website doesn't allow display of embedded content. Please select 'web link' to view the content."

  Scenario: Add new content without mandatory title
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I follow "Add new content"
    And I set the following fields to these values:
      | My Module   | 1                  |
      | Description | Just a description |
    And I press "Save"
    Then I should see "Add new content"
    And "input.is-invalid" "css_element" should exist
    Then I set the following fields to these values:
      | Title | Just a title |
    And I press "Save"
    And I follow "Shared content > My Module" in the openstudio navigation
    Then I should see "Just a title"

  Scenario: Add new content without mandatory title in My activities
    And I am on the "C1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I add a openstudio activity to course "Course 1" section "1" and I fill the form with:
      | Name                         | Test Open Studio name 2      |
      | Description                  | Test Open Studio description |
      | Group mode                   | Visible groups               |
      | Grouping                     | grouping1                    |
      | Enable pinboard              | 99                           |
      | Abuse reports are emailed to | teacher1@asd.com             |
      | ID number                    | OS2                          |
      | Enable folders               | 1                            |
      | id_tutorrolesgroup_1         | 1                            |
    And all users have accepted the plagarism statement for "OS2" openstudio
    When I am on the "Test Open Studio name 2" "openstudio activity" page
    And I navigate to "Manage levels" in current page administration
    And I press "Add another Block"
    And I set the field "Block Name" to "Block 1"
    And I press "Save Changes"
    And I follow "Block 1"
    And I press "Add another Activity"
    And I set the field "Activity Name" to "Activity 1"
    And I press "Save Changes"
    And I follow "Activity 1"
    And I press "Add another Content"
    And I set the field "Content Name" to "Content in My Activities"
    And I press "Add another Content"
    And I set the field "Content Name" to "Folder in My Activities"
    And I set the field "Is folder?" to "1"
    And I press "Save Changes"
    And I am on the "Test Open Studio name 2" "openstudio activity" page
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "a[data-name='Content in My Activities']" "css_element"
    Then the "value" attribute of "input#id_name" "css_element" should contain "Content in My Activities"
    When I am on the "Test Open Studio name 2" "openstudio activity" page
    And I follow "My Content > My Activities" in the openstudio navigation
    And I click on "a[data-name='Folder in My Activities']" "css_element"
    And I should see "Upload content to folder"
    And I click on "span.openstudio-new-folder-title" "css_element"
    Then the "value" attribute of "input#id_name" "css_element" should contain "Folder in My Activities"

  Scenario: Convert to a link for post description
    Given I am on the "C1" "Course" page logged in as "admin"
    And I navigate to "Plugins > Filters > Manage filters" in site administration
    And I click on "On" "option" in the "Convert URLs into links and images" "table_row"
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And I should see "Test Open Studio name 1"
    And I follow "Add new content"
    And I set the following fields to these values:
      | My Module   | 1                         |
      | Title       | Test link                 |
      | Description | https://learn2.open.ac.uk |
    And I press "Save"
    Then "//a[@href='https://learn2.open.ac.uk']" "xpath_element" should exist

  Scenario: Add new content just a title and description with a webm file
    When I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I follow "Add new content"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                         |
      | Title       | Test My Group Board View 7 Tags           |
      | Description | My Group Board View Description 7 Tags    |
      | Files       | mod/openstudio/tests/importfiles/test.webm |
      | Tags        | Tests Add New Tags add file webm           |
    And I wait "2" seconds
    And I should see "Tests Add New Tags add file webm"
    And I press "Save"
    And I follow "Shared content > My Module" in the openstudio navigation
    And I should see "Test My Group Board View 7 Tags"
    And I click on "//div[@class='openstudio-grid-item-content-box'][1]//a" "xpath_element"
    And I should see "Tests Add New Tags add file webm"
    Then the "href" attribute of "div.openstudio-content-view-primary a" "css_element" should contain "test.webm"
    And I should see "Test My Group Board View 7 Tags"

  Scenario: Add new content with an image file with its alt text
    Given I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I follow "Add new content"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                          |
      | Title       | Test My Group Board View 2                 |
      | Description | My Group Board View Description 2          |
      | Files       | mod/openstudio/tests/importfiles/test2.jpg |
    And I press "Save"
    And I should see "An uploaded image must include an image description for accessibility purposes"
    And I set the field "Describe this image for someone who cannot see it" to "This is image alt"
    When I press "Save"
    Then "//img[contains(@src, '/test2.jpg') and @alt='This is image alt']" "xpath_element" should exist

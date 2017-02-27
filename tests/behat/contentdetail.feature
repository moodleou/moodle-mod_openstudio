@ou @ou_vle @mod @mod_openstudio @mod_openstudio_test_content_detail @_file_upload @javascript
Feature: Create and edit contents detail
    When using Open Studio with other users
    As a teacher
    I need to navigate to content pages

    Background: Setup course and studio
        And the following "users" exist:
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
            | student4 | C1 | student |
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
            | student3 | G3 |
        And I log in as "teacher1"
        And I am on site homepage
        And I follow "Course 1"
        And I turn editing mode on
        And I add a "Open Studio" to section "1" and I fill the form with:
            | Name                         | Test Open Studio name 1      |
            | Description                  | Test Open Studio description |
            | Your word for 'My Module'    | Module 1                     |
            | Group mode                   | Separate groups              |
            | Grouping                     | grouping1                    |
            | Enable pinboard              | 99                           |
            | Abuse reports are emailed to | teacher1@asd.com             |
            | ID number                    | OS1                          |
        And Open Studio test instance is configured for "Test Open Studio name 1"
        And all users have accepted the plagarism statement for "OS1" openstudio

    Scenario: Add new content and check content details with img
        When I am on site homepage
        And I follow "Course 1"
        And I follow "Test Open Studio name 1"
        And I click on "div.openstudio-upload-container" "css_element"
        And I press "Add file"
        And I set the following fields to these values:
          | Who can view this content | My module                                  |
          | Title                     | Test My Content Details View               |
          | Description               | Test My Content Details View               |
          | Upload content            | mod/openstudio/tests/importfiles/test1.jpg |
          | Tags                      | Tests Add New Tags                         |
        And I press "Save"

        # Redirect to content detail
        And I should see "My Pinboard"
        And I should see "Teacher 1"
        And I should see "Test My Content Details View"
        And I should see "Owner of this post"
        And I should not see "Post archive"
        And the "src" attribute of "#openstudio_content_view_primary > div.openstudio-content-view-file > a > img" "css_element" should contain "test1.jpg"
        And I should see "Test My Content Details View"
        And I should see "Tests Add New Tags"
        And I scroll to the bottom of the OU study planner

        And I should see "0 Favourites"
        And I should see "0 Smiles"
        And I should see "0 Inspired"
        And I should see "0 views"

        And "Archive post" "button" should exist
        And "Report post" "button" should exist
        And I should not see "Image meta-data"
        And I should not see "Image location"
        
        And "Edit" "button" should exist
        And "Delete" "button" should exist
        And "Lock" "button" should exist
        And "Add new comment" "button" should exist
        And "Request feedback" "button" should exist

        # Post archive Block
        And I click on "#openstudio_content_view_post_owner_heading > span" "css_element"
        Then "#openstudio_content_view_post_owner > div.openstudio-content-view-user-info > a" "css_element" should exist

    Scenario: Add new content and check content details with img exif
        When I am on site homepage
        And I follow "Course 1"
        And I follow "Test Open Studio name 1"
        And the following open studio "contents" exist:
            | openstudio | user     | name                 | description             | file                                               | visibility |
            | OS1        | teacher1 | TestContentDetails 1 | Test slot 1 description | mod/openstudio/tests/importfiles/geotagged.jpg     | module     |

        # Redirect to content detail
        And I follow "Shared content > My Module" in the openstudio navigation
        And I follow "TestContentDetails 1"
        And I should see "My Pinboard"
        And I should see "Teacher 1"
        And I should see "TestContentDetails 1"
        And I should see "Owner of this post"
        And I should not see "Post archive"
        And I should not see "Image meta-data"
        And I should not see "Image location"

        # Enable Show GPS Data and Show Image Data
        And I click on "input#id_editbutton" "css_element"
        And I should see "Show GPS Data"
        And I should see "Show Image Data"
        And I click on "input#id_showgps" "css_element"
        And I click on "input#id_showimagedata" "css_element"
        And I press "Save"

        And the "src" attribute of "#openstudio_content_view_primary > div.openstudio-content-view-file > a > img" "css_element" should contain "geotagged.jpg"
        And I should see "Test slot 1 description"
        And I scroll to the bottom of the OU study planner

        And I should see "0 Favourites"
        And I should see "0 Smiles"
        And I should see "0 Inspired"
        And I should see "0 views"

        And "Archive post" "button" should exist
        And "Report post" "button" should exist
        And I should see "Image meta-data"
        And I should see "Image location"
        
        And "Edit" "button" should exist
        And "Delete" "button" should exist
        And "Lock" "button" should exist
        And "Add new comment" "button" should exist
        And "Request feedback" "button" should exist

        # Post archive Block
        And I click on "#openstudio_content_view_post_owner_heading > span" "css_element"
        And "#openstudio_content_view_post_owner > div.openstudio-content-view-user-info > a" "css_element" should exist

    Scenario: Add new content and check content details with video file
        When I am on site homepage
        And I follow "Course 1"
        And I follow "Test Open Studio name 1"
        And the following open studio "contents" exist:
            | openstudio | user     | name                 | description             | file                                              | visibility |
            | OS1        | teacher1 | TestContentDetails 2 | Test slot 2 description | mod/openstudio/tests/importfiles/test.mp4         | module     |

        # Redirect to content detail
        And I follow "Shared content > My Module" in the openstudio navigation
        And I follow "TestContentDetails 2"
        And I should see "My Pinboard"
        And I should see "Teacher 1"
        And I should see "Owner of this post"
        And I should not see "Post archive"
        And I should not see "Image meta-data"
        And I should not see "Image location"

        # Enable Show GPS Data and Show Image Data
        And I click on "input#id_editbutton" "css_element"
        And I should see "Show GPS Data"
        And I should see "Show Image Data"
        And I click on "input#id_showgps" "css_element"
        And I click on "input#id_showimagedata" "css_element"
        And I press "Save"

        And I should see "TestContentDetails 2"
        And I should see "Test slot 2 description"
        And I should see "Download file attachment"
        And I scroll to the bottom of the OU study planner

        And I should see "0 Favourites"
        And I should see "0 Smiles"
        And I should see "0 Inspired"
        And I should see "0 views"

        And "Archive post" "button" should exist
        And "Report post" "button" should exist
        And I should not see "Image meta-data"
        And I should not see "Image location"
        
        And "Edit" "button" should exist
        And "Delete" "button" should exist
        And "Lock" "button" should exist
        And "Add new comment" "button" should exist
        And "Request feedback" "button" should exist

        # Post archive Block
        And I click on "#openstudio_content_view_post_owner_heading > span" "css_element"
        And "#openstudio_content_view_post_owner > div.openstudio-content-view-user-info > a" "css_element" should exist

    Scenario: Add new content and check content details with audio file
        When I am on site homepage
        And I follow "Course 1"
        And I follow "Test Open Studio name 1"
        And the following open studio "contents" exist:
            | openstudio | user     | name                 | description             | file                                              | visibility |
            | OS1        | teacher1 | TestContentDetails 3 | Test slot 3 description | mod/openstudio/tests/importfiles/test.mp3         | module     |

        # Redirect to content detail
        And I follow "Shared content > My Module" in the openstudio navigation
        And I follow "TestContentDetails 3"
        And I should see "My Pinboard"
        And I should see "Teacher 1"
        And I should see "Owner of this post"
        And I should not see "Post archive"
        And I should not see "Image meta-data"
        And I should not see "Image location"

        # Enable Show GPS Data and Show Image Data
        And I click on "input#id_editbutton" "css_element"
        And I should see "Show GPS Data"
        And I should see "Show Image Data"
        And I click on "input#id_showgps" "css_element"
        And I click on "input#id_showimagedata" "css_element"
        And I press "Save"

        And I should see "TestContentDetails 3"
        And I should see "Test slot 3 description"
        And I should see "Download file attachment"
        And I scroll to the bottom of the OU study planner

        And I should see "0 Favourites"
        And I should see "0 Smiles"
        And I should see "0 Inspired"
        And I should see "0 views"

        And "Archive post" "button" should exist
        And "Report post" "button" should exist
        And I should not see "Image meta-data"
        And I should not see "Image location"
        
        And "Edit" "button" should exist
        And "Delete" "button" should exist
        And "Lock" "button" should exist
        And "Add new comment" "button" should exist
        And "Request feedback" "button" should exist

        # Post archive Block
        And I click on "#openstudio_content_view_post_owner_heading > span" "css_element"
        And "#openstudio_content_view_post_owner > div.openstudio-content-view-user-info > a" "css_element" should exist

    Scenario: Add new content and check content details with web/embed link
        When I am on site homepage
        And I follow "Course 1"
        And I follow "Test Open Studio name 1"
        And I click on "div.openstudio-upload-container" "css_element"
        And I press "Add web/embed link"
        And I set the following fields to these values:
          | Who can view this content | My module                                            |
          | Title                     | Test My Content Details View 4                       |
          | Description               | My Content Details View Description 4                |
          | Web link                  | https://www.youtube.com/watch?v=ktAnpf_nu5c          |
          | Tags                      | Tests Add New Tags  4                                |
        And I press "Save"

        # Redirect to content detail
        And I should see "My Pinboard"
        And I should see "Teacher 1"
        And I should see "Test My Content Details View"
        And I should see "Owner of this post"
        And I should not see "Post archive"
        And I should not see "Show GPS Data"
        And I should not see "Show Image Data"

        And I should see "My Content Details View Description 4"
        And I should see "Tests Add New Tags 4"
        And I scroll to the bottom of the OU study planner

        And I should see "0 Favourites"
        And I should see "0 Smiles"
        And I should see "0 Inspired"
        And I should see "0 views"

        And "Archive post" "button" should exist
        And "Report post" "button" should exist
        And I should not see "Image meta-data"
        And I should not see "Image location"
        
        And "Edit" "button" should exist
        And "Delete" "button" should exist
        And "Lock" "button" should exist
        And "Add new comment" "button" should exist
        And "Request feedback" "button" should exist

        # Post archive Block
        And I click on "#openstudio_content_view_post_owner_heading > span" "css_element"
        And "#openstudio_content_view_post_owner > div.openstudio-content-view-user-info > a" "css_element" should exist

    Scenario: Add new content and check content details with Documents
        When I am on site homepage
        And I follow "Course 1"
        And I follow "Test Open Studio name 1"
        And the following open studio "contents" exist:
            | openstudio | user     | name                 | description             | file                                              | visibility |
            | OS1        | teacher1 | TestContentDetails 5 | Test slot 5 description | mod/openstudio/tests/importfiles/test.pdf         | module     |

        # Redirect to content detail
        And I follow "Shared content > My Module" in the openstudio navigation
        And I follow "TestContentDetails 5"
        And I should see "My Pinboard"
        And I should see "Teacher 1"
        And I should see "Owner of this post"
        And I should not see "Post archive"
        And I should not see "Image meta-data"
        And I should not see "Image location"

        # Enable Show GPS Data and Show Image Data
        And I click on "input#id_editbutton" "css_element"
        And I should see "Show GPS Data"
        And I should see "Show Image Data"
        And I click on "input#id_showgps" "css_element"
        And I click on "input#id_showimagedata" "css_element"
        And I press "Save"

        And I should see "TestContentDetails 5"
        And I should see "Test slot 5 description"
        And I should see "Download file attachment"
        And I scroll to the bottom of the OU study planner

        And I should see "0 Favourites"
        And I should see "0 Smiles"
        And I should see "0 Inspired"
        And I should see "0 views"

        And "Archive post" "button" should exist
        And "Report post" "button" should exist
        And I should not see "Image meta-data"
        And I should not see "Image location"
        
        And "Edit" "button" should exist
        And "Delete" "button" should exist
        And "Lock" "button" should exist
        And "Add new comment" "button" should exist
        And "Request feedback" "button" should exist

        # Post archive Block
        And I click on "#openstudio_content_view_post_owner_heading > span" "css_element"
        And "#openstudio_content_view_post_owner > div.openstudio-content-view-user-info > a" "css_element" should exist

    Scenario: Add new content and check content details with Spreadsheets (subject to the OS Setting)
        When I am on site homepage
        And I follow "Course 1"
        And I follow "Test Open Studio name 1"
        And the following open studio "contents" exist:
            | openstudio | user     | name                 | description             | file                                              | visibility |
            | OS1        | teacher1 | TestContentDetails 6 | Test slot 6 description | mod/openstudio/tests/importfiles/test.xlsx        | module     |

        # Redirect to content detail
        And I follow "Shared content > My Module" in the openstudio navigation
        And I follow "TestContentDetails 6"
        And I should see "My Pinboard"
        And I should see "Teacher 1"
        And I should see "Owner of this post"
        And I should not see "Post archive"
        And I should not see "Image meta-data"
        And I should not see "Image location"

        # Enable Show GPS Data and Show Image Data
        And I click on "input#id_editbutton" "css_element"
        And I should see "Show GPS Data"
        And I should see "Show Image Data"
        And I click on "input#id_showgps" "css_element"
        And I click on "input#id_showimagedata" "css_element"
        And I press "Save"

        And I should see "TestContentDetails 6"
        And I should see "Test slot 6 description"
        And I should see "Download file attachment"
        And I scroll to the bottom of the OU study planner

        And I should see "0 Favourites"
        And I should see "0 Smiles"
        And I should see "0 Inspired"
        And I should see "0 views"

        And "Archive post" "button" should exist
        And "Report post" "button" should exist
        And I should not see "Image meta-data"
        And I should not see "Image location"
        
        And "Edit" "button" should exist
        And "Delete" "button" should exist
        And "Lock" "button" should exist
        And "Add new comment" "button" should exist
        And "Request feedback" "button" should exist

        # Post archive Block
        And I click on "#openstudio_content_view_post_owner_heading > span" "css_element"
        And "#openstudio_content_view_post_owner > div.openstudio-content-view-user-info > a" "css_element" should exist

    Scenario: Add new content and check content details with dulicated
        When I am on site homepage
        And I follow "Course 1"
        And I follow "Test Open Studio name 1"
        And the following open studio "contents" exist:
            | openstudio | user     | name                 | description             | file                                              | visibility |
            | OS1        | teacher1 | TestContentDetails 7 | Test slot 7 description | mod/openstudio/tests/importfiles/test1.jpg        | module     |
        And the following open studio "contents" exist:
            | openstudio | user     | name                 | description             | file                                              | visibility |
            | OS1        | teacher1 | TestContentDetails 8 | Test slot 8 description | mod/openstudio/tests/importfiles/test1.jpg        | module     |

        # Redirect to content detail
        And I follow "Shared content > My Module" in the openstudio navigation
        And I follow "TestContentDetails 7"
        And I should see "My Pinboard"
        And I should see "Teacher 1"
        And I should see "Owner of this post"
        And I should not see "Post archive"
        And I should not see "Image meta-data"
        And I should not see "Image location"
        And I should see "2 copies"

    Scenario: Add new content and check content details with another user
        When I am on site homepage
        And I follow "Course 1"
        And I follow "Test Open Studio name 1"
        And the following open studio "contents" exist:
            | openstudio | user     | name                 | description             | file                                              | visibility |
            | OS1        | teacher1 | TestContentDetails 9 | Test slot 9 description | mod/openstudio/tests/importfiles/test1.jpg        | module     |

        # Redirect to content detail
        And I follow "Shared content > My Module" in the openstudio navigation
        And I follow "TestContentDetails 9"
        And I should see "0 views"

        # switch to student1
        And I am on site homepage
        And I log out
        And I log in as "student1"
        And I follow "Course 1"
        And I follow "Test Open Studio name 1"
        And I follow "Shared content > My Module" in the openstudio navigation
        And I click on "div.openstudio-grid-item-content-preview > a > img" "css_element"
        And I should see "Teacher 1"
        And I should see "TestContentDetails 9"
        And I should see "Owner of this post"
        And I should not see "Post archive"
        And the "src" attribute of "#openstudio_content_view_primary > div.openstudio-content-view-file > a > img" "css_element" should contain "test1.jpg"
        And I should see "TestContentDetails 9"
        And I scroll to the bottom of the OU study planner
        And I should see "1 views"

        # switch to student2
        And I am on site homepage
        And I log out
        And I log in as "student2"
        And I follow "Course 1"
        And I follow "Test Open Studio name 1"
        And I follow "Shared content > My Module" in the openstudio navigation
        And I click on "div.openstudio-grid-item-content-preview > a > img" "css_element"
        And I should see "Teacher 1"
        And I should see "TestContentDetails 9"
        And I should see "Owner of this post"
        And I should not see "Post archive"
        And the "src" attribute of "#openstudio_content_view_primary > div.openstudio-content-view-file > a > img" "css_element" should contain "test1.jpg"
        And I should see "TestContentDetails 9"
        And I scroll to the bottom of the OU study planner
        And I should see "2 views"

        # switch to teacher1 again
        And I am on site homepage
        And I log out
        And I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Open Studio name 1"
        And I follow "Shared content > My Module" in the openstudio navigation
        And I click on "div.openstudio-grid-item-content-preview > a > img" "css_element"
        And I should see "Teacher 1"
        And I should see "TestContentDetails 9"
        And I should see "Owner of this post"
        And I should not see "Post archive"
        And the "src" attribute of "#openstudio_content_view_primary > div.openstudio-content-view-file > a > img" "css_element" should contain "test1.jpg"
        And I should see "TestContentDetails 9"
        And I scroll to the bottom of the OU study planner
        And I should see "2 views"

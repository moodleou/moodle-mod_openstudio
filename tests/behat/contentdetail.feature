@ou @ou_vle @mod @mod_openstudio @mod_openstudio_test_content_detail @_file_upload @javascript
Feature: Create and edit Open Studio contents detail
When using Open Studio with other users
As a teacher
I need to navigate to content pages

  Background: Setup course and studio
    And the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
      | student2 | Student   | 2        | student2@asd.com |
      | student3 | Student   | 3        | student3@asd.com |
      | student4 | Student   | 4        | student4@asd.com |
      | teacher2 | Teacher   | 2        | teacher2@asd.com |
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
      | teacher2 | C1     | editingteacher |
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
      | teacher2 | G1    |
    And the following config values are set as admin:
      | file_redactor_exifremoverenabled | 0 |
    And I am on the "Course 1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I wait until the page is ready
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

  @_file_upload
  Scenario: Add new content and check content details with img
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "Upload content"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                          |
      | Title       | Test My Content Details View               |
      | Description | Test My Content Details View               |
      | Files       | mod/openstudio/tests/importfiles/test1.jpg |
      | Tags        | Tests Add New Tags                         |
    And I set the field "Describe this image for someone who cannot see it" to "This is image alt"
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

    And I should see "0 Favourites"
    And I should see "0 Smiles"
    And I should see "0 Inspired"
    And I should see "0 views"

    And "Archive post" "button" should exist
    And "Report post" "button" should not exist
    And I should not see "Image meta-data"
    And I should not see "Image location"

    And "Edit" "button" should exist
    And "Delete" "button" should exist
    And "Lock" "button" should exist
    And "Add new comment" "button" should exist
    And "Request feedback" "button" should exist

        # Post archive Block
    And I press "Owner of this post"
    Then "#openstudio_content_view_post_owner > div.openstudio-content-view-user-info > a" "css_element" should exist

  @_file_upload
  Scenario: Add new content and check content details with img exif
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And the following open studio "contents" exist:
      | openstudio | user     | name                 | description             | file                                           | visibility | retainimagemetadata | enteralt          |
      | OS1        | teacher1 | TestContentDetails 1 | Test slot 1 description | mod/openstudio/tests/importfiles/geotagged.jpg | module     | 1                   | This is image alt |

        # Redirect to content detail
    And I follow "People" in the openstudio navigation
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
    And I should see "Show GPS data"
    And I should see "Show image data"
    And I click on "input#id_showgps" "css_element"
    And I click on "input#id_showimagedata" "css_element"
    And I press "Save"

    And the "src" attribute of "#openstudio_content_view_primary > div.openstudio-content-view-file > a > img" "css_element" should contain "geotagged.jpg"
    And I wait until the page is ready
    And I should see "Test slot 1 description"

    And I should see "0 Favourites"
    And I should see "0 Smiles"
    And I should see "0 Inspired"
    And I should see "0 views"

    And "Archive post" "button" should exist
    And "Report post" "button" should not exist
    And I should see "Image meta-data"
    And I should see "Image location"

    And "Edit" "button" should exist
    And "Delete" "button" should exist
    And "Lock" "button" should exist
    And "Add new comment" "button" should exist
    And "Request feedback" "button" should exist

    # Post archive Block
    And I press "Owner of this post"
    And "#openstudio_content_view_post_owner > div.openstudio-content-view-user-info > a" "css_element" should exist

  Scenario: Add new content and check content details with video file
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And the following open studio "contents" exist:
      | openstudio | user     | name                 | description             | file                                      | visibility |
      | OS1        | teacher1 | TestContentDetails 2 | Test slot 2 description | mod/openstudio/tests/importfiles/test.mp4 | module     |

    # Redirect to content detail
    And I follow "People" in the openstudio navigation
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
    And I should see "Show GPS data"
    And I should see "Show image data"
    And I click on "input#id_showgps" "css_element"
    And I click on "input#id_showimagedata" "css_element"
    And I click on "input#id_retainimagemetadata" "css_element"
    And I press "Save"

    And I should see "TestContentDetails 2"
    And I should see "Test slot 2 description"
    And I should see "Download file attachment"

    And I should see "0 Favourites"
    And I should see "0 Smiles"
    And I should see "0 Inspired"
    And I should see "0 views"

    And "Archive post" "button" should exist
    And "Report post" "button" should not exist
    And I should not see "Image meta-data"
    And I should not see "Image location"

    And "Edit" "button" should exist
    And "Delete" "button" should exist
    And "Lock" "button" should exist
    And "Add new comment" "button" should exist
    And "Request feedback" "button" should exist

    # Post archive Block
    And I press "Owner of this post"
    And "#openstudio_content_view_post_owner > div.openstudio-content-view-user-info > a" "css_element" should exist

  Scenario: Add new content and check content details with audio file
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And the following open studio "contents" exist:
      | openstudio | user     | name                 | description             | file                                      | visibility |
      | OS1        | teacher1 | TestContentDetails 3 | Test slot 3 description | mod/openstudio/tests/importfiles/test.mp3 | module     |

    # Redirect to content detail
    And I follow "People" in the openstudio navigation
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
    And I should see "Show GPS data"
    And I should see "Show image data"
    And I click on "input#id_showgps" "css_element"
    And I click on "input#id_showimagedata" "css_element"
    And I click on "input#id_retainimagemetadata" "css_element"
    And I press "Save"

    And I should see "TestContentDetails 3"
    And I should see "Test slot 3 description"
    And I should see "Download file attachment"

    And I should see "0 Favourites"
    And I should see "0 Smiles"
    And I should see "0 Inspired"
    And I should see "0 views"

    And "Archive post" "button" should exist
    And "Report post" "button" should not exist
    And I should not see "Image meta-data"
    And I should not see "Image location"

    And "Edit" "button" should exist
    And "Delete" "button" should exist
    And "Lock" "button" should exist
    And "Add new comment" "button" should exist
    And "Request feedback" "button" should exist

    # Post archive Block
    And I press "Owner of this post"
    And "#openstudio_content_view_post_owner > div.openstudio-content-view-user-info > a" "css_element" should exist

  Scenario: Add new content and check content details with web/embed link
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And I follow "Upload content"
    And I press "Add web/embed link"
    And I set the following fields to these values:
      | My Module   | 1                                           |
      | Title       | Test My Content Details View 4              |
      | Description | My Content Details View Description 4       |
      | Web link    | https://www.youtube.com/watch?v=ktAnpf_nu5c |
      | Tags        | Tests Add New Tags  4                       |
    And I press "Save"

    # Redirect to content detail
    And I should see "My Pinboard"
    And I should see "Teacher 1"
    And I should see "Test My Content Details View"
    And I should see "Owner of this post"
    And I should not see "Post archive"
    And I should not see "Show GPS data"
    And I should not see "Show image data"

    And I should see "My Content Details View Description 4"
    And I should see "Tests Add New Tags 4"

    And I should see "0 Favourites"
    And I should see "0 Smiles"
    And I should see "0 Inspired"
    And I should see "0 views"

    And "Archive post" "button" should exist
    And "Report post" "button" should not exist
    And I should not see "Image meta-data"
    And I should not see "Image location"

    And "Edit" "button" should exist
    And "Delete" "button" should exist
    And "Lock" "button" should exist
    And "Add new comment" "button" should exist
    And "Request feedback" "button" should exist

    # Post archive Block
    And I press "Owner of this post"
    And "#openstudio_content_view_post_owner > div.openstudio-content-view-user-info > a" "css_element" should exist

  Scenario: Add new content and check content details with Documents
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And the following open studio "contents" exist:
      | openstudio | user     | name                 | description             | file                                      | visibility |
      | OS1        | teacher1 | TestContentDetails 5 | Test slot 5 description | mod/openstudio/tests/importfiles/test.pdf | module     |

    # Redirect to content detail
    And I follow "People" in the openstudio navigation
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
    And I should see "Show GPS data"
    And I should see "Show image data"
    And I click on "input#id_showgps" "css_element"
    And I click on "input#id_showimagedata" "css_element"
    And I click on "input#id_retainimagemetadata" "css_element"
    And I press "Save"

    And I should see "TestContentDetails 5"
    And I should see "Test slot 5 description"
    And I should see "Download file attachment"

    And I should see "0 Favourites"
    And I should see "0 Smiles"
    And I should see "0 Inspired"
    And I should see "0 views"

    And "Archive post" "button" should exist
    And "Report post" "button" should not exist
    And I should not see "Image meta-data"
    And I should not see "Image location"

    And "Edit" "button" should exist
    And "Delete" "button" should exist
    And "Lock" "button" should exist
    And "Add new comment" "button" should exist
    And "Request feedback" "button" should exist

    # Post archive Block
    And I press "Owner of this post"
    And "#openstudio_content_view_post_owner > div.openstudio-content-view-user-info > a" "css_element" should exist

  Scenario: Add new content and check content details with Spreadsheets (subject to the OS Setting)
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And the following open studio "contents" exist:
      | openstudio | user     | name                 | description             | file                                       | visibility |
      | OS1        | teacher1 | TestContentDetails 6 | Test slot 6 description | mod/openstudio/tests/importfiles/test.xlsx | module     |

    # Redirect to content detail
    And I follow "People" in the openstudio navigation
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
    And I should see "Show GPS data"
    And I should see "Show image data"
    And I click on "input#id_showgps" "css_element"
    And I click on "input#id_showimagedata" "css_element"
    And I click on "input#id_retainimagemetadata" "css_element"
    And I press "Save"

    And I should see "TestContentDetails 6"
    And I should see "Test slot 6 description"
    And I should see "Download file attachment"

    And I should see "0 Favourites"
    And I should see "0 Smiles"
    And I should see "0 Inspired"
    And I should see "0 views"

    And "Archive post" "button" should exist
    And "Report post" "button" should not exist
    And I should not see "Image meta-data"
    And I should not see "Image location"

    And "Edit" "button" should exist
    And "Delete" "button" should exist
    And "Lock" "button" should exist
    And "Add new comment" "button" should exist
    And "Request feedback" "button" should exist

    # Post archive Block
    And I press "Owner of this post"
    And "#openstudio_content_view_post_owner > div.openstudio-content-view-user-info > a" "css_element" should exist

  Scenario: Add new content and check content details with dulicated
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And the following open studio "contents" exist:
      | openstudio | user     | name                 | description             | file                                       | visibility |
      | OS1        | teacher1 | TestContentDetails 7 | Test slot 7 description | mod/openstudio/tests/importfiles/test1.jpg | module     |
    And the following open studio "contents" exist:
      | openstudio | user     | name                 | description             | file                                       | visibility |
      | OS1        | teacher1 | TestContentDetails 8 | Test slot 8 description | mod/openstudio/tests/importfiles/test1.jpg | module     |

    # Redirect to content detail
    And I follow "People" in the openstudio navigation
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
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And the following open studio "contents" exist:
      | openstudio | user     | name                 | description             | file                                       | visibility |
      | OS1        | teacher1 | TestContentDetails 9 | Test slot 9 description | mod/openstudio/tests/importfiles/test1.jpg | module     |

    # Redirect to content detail
    And I follow "People" in the openstudio navigation
    And I follow "Shared content > My Module" in the openstudio navigation
    And I follow "TestContentDetails 9"
    And I should see "0 views"

    # switch to student1
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "student1"
    And I follow "People" in the openstudio navigation
    And I follow "Shared content > My Module" in the openstudio navigation
    And I follow "TestContentDetails 9"
    And I should see "Teacher 1"
    And I should see "TestContentDetails 9"
    And I should see "Owner of this post"
    And I should not see "Post archive"
    And the "src" attribute of "#openstudio_content_view_primary > div.openstudio-content-view-file > a > img" "css_element" should contain "test1.jpg"
    And I should see "TestContentDetails 9"
    And I should see "1 views"

    # switch to student2
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "student2"
    And I follow "People" in the openstudio navigation
    And I follow "Shared content > My Module" in the openstudio navigation
    And I follow "TestContentDetails 9"
    And I should see "Teacher 1"
    And I should see "TestContentDetails 9"
    And I should see "Owner of this post"
    And I should not see "Post archive"
    And the "src" attribute of "#openstudio_content_view_primary > div.openstudio-content-view-file > a > img" "css_element" should contain "test1.jpg"
    And I should see "TestContentDetails 9"
    And I should see "2 views"

    # switch to teacher1 again
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I follow "People" in the openstudio navigation
    And I follow "Shared content > My Module" in the openstudio navigation
    And I follow "TestContentDetails 9"
    And I should see "Teacher 1"
    And I should see "TestContentDetails 9"
    And I should see "Owner of this post"
    And I should not see "Post archive"
    And the "src" attribute of "#openstudio_content_view_primary > div.openstudio-content-view-file > a > img" "css_element" should contain "test1.jpg"
    And I should see "TestContentDetails 9"
    And I should see "2 views"

  Scenario: Request feedback on students work
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And the following open studio "contents" exist:
      | openstudio | user     | name                         | description                  | file                                       | visibility |
      | OS1        | teacher1 | Test My Content Details View | Test My Content Details View | mod/openstudio/tests/importfiles/test1.jpg | module     |
    And I follow "People" in the openstudio navigation
    And I follow "Shared content > My Module" in the openstudio navigation
    And I follow "Test My Content Details View"
    And "Request feedback" "button" should exist

    # Test show Requested feedback
    And I press "Request feedback"
    And I reload the page
    And "Cancel feedback request" "button" should exist
    And "Request feedback" "button" should not exist
    And I should see "Feedback requested" in the "div#openstudio_item_request_feedback" "css_element"
    And the "class" attribute of "div#openstudio_item_request_feedback" "css_element" should contain "openstudio-item-request-feedback"

    # Cancel Requested feedback
    And I press "Cancel feedback request"
    And I should not see "Feedback requested" in the "div#openstudio_item_request_feedback" "css_element"
    And the "class" attribute of "div#openstudio_item_request_feedback" "css_element" should contain "openstudio-item-request-feedback-cancel"

    # switch to student1
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "student1"
    And I follow "People" in the openstudio navigation
    And I follow "Shared content > My Module" in the openstudio navigation
    And I follow "Test My Content Details View"
    And "Request feedback" "button" should not exist

    # switch to student2
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "student2"
    And I follow "People" in the openstudio navigation
    And I follow "Shared content > My Module" in the openstudio navigation
    And I follow "Test My Content Details View"
    And "Request feedback" "button" should not exist

  Scenario: Archive post content details
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And the following open studio "contents" exist:
      | openstudio | user     | name                         | description                  | file                                       | visibility |
      | OS1        | teacher1 | Test My Content Details View | Test My Content Details View | mod/openstudio/tests/importfiles/test1.jpg | module     |
    And I follow "People" in the openstudio navigation
    And I follow "Shared content > My Module" in the openstudio navigation
    And I follow "Test My Content Details View"
    And I should not see "Post archive"
    And "Archive post" "button" should exist
    And I press "Archive post"
    And I should see "Are you sure you want to archive the content?"

    # Press Archive post in Archive post popup
    And I click on "Archive post" "button" in the "Archive post" "dialogue"

    # Add new content Archive post
    And I follow "Edit this post"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                          |
      | Title       | Test My Content Details View Archive 1     |
      | Description | Test My Content Details View Archive 1     |
      | Files       | mod/openstudio/tests/importfiles/test2.jpg |
      | Tags        | Tests Add New Tags                         |
    And I set the field "Describe this image for someone who cannot see it" to "This is image alt 1"
    And I press "Save"
    And I should see "Post archive"
    And I press "Post archive"
    And I should see "Test My Content Details View"

    # View content Archive post
    And I click on "viewversionbutton" "button"
    And I should see "Version 1 of 1"
    And "Current version" "button" should exist

    # View content Current version
    And I press "Current version"
    And I should see "Test My Content Details View Archive 1"

    # View Current version in My Module
    And I follow "People" in the openstudio navigation
    And I follow "Shared content > My Module" in the openstudio navigation
    And I should see "Test My Content Details View Archive 1"

    # Restore this version
    And I follow "Test My Content Details View Archive 1"
    And I press "Archive post"
    And I click on "Archive post" "button" in the "Archive post" "dialogue"
    And I follow "Edit this post"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                          |
      | Title       | Test My Content Details View Archive 2     |
      | Description | Test My Content Details View Archive 2     |
      | Files       | mod/openstudio/tests/importfiles/test3.jpg |
      | Tags        | Tests Add New Tags                         |
    And I set the field "Describe this image for someone who cannot see it" to "This is image alt"
    And I press "Save"
    And I press "Post archive"
    And I should see "Test My Content Details View Archive"
    And I should see "Test My Content Details View Archive 1"
    And I click on "viewversionbutton" "button"
    And I press "Restore this version"
    And I should see "Test My Content Details View Archive 1"

    # Verify that the image alt text is retained after archiving and restoring.
    And "//img[contains(@src, '/test2.jpg') and @alt='This is image alt 1']" "xpath_element" should exist
    And I press "Edit"
    Then the following fields match these values:
      | Describe this image for someone who cannot see it | This is image alt 1 |
    And I set the field "Describe this image for someone who cannot see it" to "This is image alt 2"
    And I press "Save"
    And "//img[contains(@src, '/test2.jpg') and @alt='This is image alt 2']" "xpath_element" should exist

    And I follow "People" in the openstudio navigation
    And I follow "Shared content > My Module" in the openstudio navigation
    And I should see "Test My Content Details View Archive 1"

    # switch to teacher2, delete button will be show
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher2"
    And I follow "Test My Content Details View Archive 1"
    And I should not see "Archive post"
    And I press "Post archive"
    And I click on "viewversionbutton" "button"
    And "Delete" "button" should exist

    # switch to student1, delete button will be hide
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "student1"
    And I follow "Test My Content Details View Archive 1"
    And I should not see "Archive post"
    And I press "Post archive"
    And I click on "viewversionbutton" "button"
    And I should not see "Delete"

    # switch to student2, delete button will be hide
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "student2"
    And I follow "Test My Content Details View Archive 1"
    And I should not see "Archive post"
    And I press "Post archive"
    And I click on "viewversionbutton" "button"
    And I should not see "Delete"

    # Delete Archive post
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And I follow "Test My Content Details View Archive 1"
    And I press "Post archive"
    And I should see "Test My Content Details View Archive 2"
    And I press "Delete"
    And I click on "Delete" "button" in the "Delete post?" "dialogue"
    And I should not see "Test My Content Details View Archive 2"

  Scenario: Breadcrumb navigation for View content details
    And I am on the "Test Open Studio name 1" "openstudio activity" page

    # Breadcrumb view content details
    And the following open studio "contents" exist:
      | openstudio | user     | name                         | description                  | file                                       | visibility |
      | OS1        | teacher1 | Test My Content Details View | Test My Content Details View | mod/openstudio/tests/importfiles/test1.jpg | module     |
    And I follow "People" in the openstudio navigation
    And I follow "Shared content > My Module" in the openstudio navigation
    And I follow "Test My Content Details View"
    And the openstudio breadcrumbs should be "C1 > New section > Test Open Studio name 1 > My Pinboard > Test My Content Details View"

    # Breadcrumb add content
    And I follow "Shared content > My Module" in the openstudio navigation
    And I follow "Upload content"
    And the openstudio breadcrumbs should be "C1 > New section > Test Open Studio name 1 > My Pinboard > Pinboard content > Create"

    # switch to student1
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "student1"
    And I follow "People" in the openstudio navigation
    And I follow "Shared content > My Module" in the openstudio navigation
    And I follow "Test My Content Details View"
    Then the openstudio breadcrumbs should be "C1 > New section > Test Open Studio name 1 > My Module > Teacher's work > Test My Content Details View"

  Scenario: Check flags setting applied
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Flags | Favourite, Made me laugh |
    And I press "Save and display"
    And the following open studio "contents" exist:
      | openstudio | user     | name         | description                | visibility |
      | OS1        | teacher1 | TestContent1 | Test content 1 description | module     |

    And I follow "People" in the openstudio navigation
    And I follow "Shared content > My Module" in the openstudio navigation
    And I follow "TestContent1"
    And I should see "0 Favourites"
    And I should see "0 Smiles"
    And I should not see "0 Inspired"
    And "Request feedback" "button" should not exist

    # Check flags setting applied for filter
    And I follow "Shared content > My Module" in the openstudio navigation
    And I press "Filter"
    And I should see "Favourite"
    And I should see "Smile"
    And I should not see "Inspiration"
    And I should not see "Feedback requested"

  @_file_upload
  Scenario: Add new content and check content details with abitily to remove image exif.
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And the following open studio "contents" exist:
      | openstudio | user     | name                 | description             | file                                           | visibility | retainimagemetadata | showimagedata | showgps | enteralt          |
      | OS1        | teacher1 | TestContentDetails 1 | Test slot 1 description | mod/openstudio/tests/importfiles/geotagged.jpg | module     | 0                   | 0             | 0       | This is image alt |
    # Redirect to content detail
    And I follow "People" in the openstudio navigation
    And I follow "Shared content > My Module" in the openstudio navigation
    And I follow "TestContentDetails 1"
    And I should see "My Pinboard"
    And I should see "Teacher 1"
    And I should see "TestContentDetails 1"
    And I should see "Owner of this post"
    # Enable Show GPS Data and Show Image Data but don't check retain exif data.
    # Following will fail if Imagick not installed as validation of these fields is then disabled.
    And I click on "input#id_editbutton" "css_element"
    And I click on "input#id_showgps" "css_element"
    And I press "Save"
    And I should see "In order to show GPS or Image data the EXIF image data must be retained"
    And I click on "input#id_showimagedata" "css_element"
    And I press "Save"
    And I should see "In order to show GPS or Image data the EXIF image data must be retained"
    And I click on "input#id_retainimagemetadata" "css_element"
    And I press "Save"
    Then I should see "Image meta-data"
    And I should see "Image location"
    And I follow "People" in the openstudio navigation
    And I follow "Shared content > My Module" in the openstudio navigation
    And I click on "Upload content" "text"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module     | 1                                              |
      | Title         | Test My Content Details View                   |
      | Description   | Test My Content Details View                   |
      | Files         | mod/openstudio/tests/importfiles/geotagged.jpg |
      | Tags          | Tests Add New Tags                             |
      | showimagedata | 1                                              |
      | showgps       | 1                                              |
    And I set the field "Describe this image for someone who cannot see it" to "This is image alt"
    And I press "Save"
    Then I should not see "Image meta-data"

  Scenario: Add new content and check preview for ipynb files and export is working fine with 3 files nbk.
    When I am on the "Test Open Studio name 1" "openstudio activity" page
    And I click on "Upload content" "text"
    And I press "Add file"
    And I set the following fields to these values:
      | My Module   | 1                                           |
      | Title       | Test My Content Details View                |
      | Description | Test My Content Details View                |
      | Files       | mod/openstudio/tests/importfiles/test.ipynb |
    And I press "Save"
    When I wait until "Graphs in Statistical Analysis" "text" exists
    Then I should see "usefulness of graphs"
    And "#openstudio-content-previewipynb" "css_element" should exist
    And I press "Edit"
    And "test.ipynb" "text" should exist in the ".filemanager-container" "css_element"
    And I am logged in as "admin"
    And the following config values are set as admin:
      | enableportfolios | 1 |
    And I navigate to "Plugins > Manage portfolios" in site administration
    And I set portfolio instance "File download" to "Enabled and visible"
    And I press "Save"
    And I am on the "Test Open Studio name 1" "openstudio activity" page logged in as "teacher1"
    And the following open studio "contents" exist:
      | openstudio | user     | name                 | description             | file                                        |
      | OS1        | teacher1 | TestContentDetails 1 | Test slot 1 description | mod/openstudio/tests/importfiles/3files.nbk |
    And I follow "My Content"
    And I follow "My Pinboard"
    And I follow "Export"
    And I press "All content shown"
    Then I should see "Downloading ..."

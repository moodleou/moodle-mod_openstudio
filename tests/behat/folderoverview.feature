@ou @ou_vle @mod @mod_openstudio @mod_openstudio_folder_overview @javascript
Feature: Open Studio Folder Overview
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
      And the following "groups" exist:
          | name     | course | idnumber |
          | group1   | C1     | G1       |
      And the following "groupings" exist:
          | name      | course | idnumber |
          | grouping1 | C1     | GI1      |
      And the following "grouping groups" exist:
          | grouping | group |
          | GI1      | G1    |
      And the following "group members" exist:
          | user     | group  |
          | teacher1 | G1     |
          | student1 | G1     |
          | student2 | G1     |
          | student3 | G1     |
      And the following open studio "instances" exist:
          | course | name                    | description                  | pinboard | idnumber | groupmode | grouping  | pinboard | enablefolders | reportingemail   |
          | C1     | Test Open Studio name 1 | Test Open Studio description | 99       | OS1      | 2         | GI1       | 99       | 1             | teacher1@asd.com |
      # Use Legacy system for default.
      Given the following config values are set as admin:
          | modulesitesearch | 2 | local_moodleglobalsearch |
          | activitysearch   | 1 | local_moodleglobalsearch |
      And I log in as "teacher1"
      And I am on "Course 1" course homepage
      And all users have accepted the plagarism statement for "OS1" openstudio
      And I change viewport size to "large"

  Scenario: Check Item Folder Overview
      Given the following open studio "folders" exist:
        | openstudio | user     | name                   | description                       | visibility | contenttype    | tags     |
        | OS1        | teacher1 | Test Folder Overview   | My Folder Overview Description 1  | module     | folder_content | testtag  |
      And I follow "Test Open Studio name 1"
      And I follow "People" in the openstudio navigation
      And I follow "Shared Content > My Module" in the openstudio navigation
      And I follow "Test Folder Overview"
      And I should see "Folder Overview"

      # the left handside should be the post stream
      And I should see "Test Folder Overview"
      And I should see "Edit folder details and sharing"

      And I should see "Add new content"
      And I should see "Upload content to folder"
      And I should see "Select existing post to add to folder"

      # the items in the right handside
      And I should see "Owner of this folder"
      And I press "Owner of this folder"
      And I should see "Teacher 1"
      And I should see "Folder description"
      And I press "Folder description"
      And I should see "My Folder Overview Description 1"
      And I should see "Folder comments"
      And I press "Folder comments"
      And "Add new comment" "button" should exist
      And I should see "Folder tags"
      And I press "Folder tags"
      And I should see "testtag"

      And "Delete folder" "button" should exist
      And "Lock folder" "button" should exist
      And "Request feedback" "button" should exist

      And I should see "0 Favourites"
      And I should see "0 Smiles"
      And I should see "0 Inspired"
      And I should see "0 views"

  @_file_upload
  Scenario: Upload content in Folder Overview
      Given the following open studio "folders" exist:
        | openstudio | user     | name                   | description                       | visibility | contenttype    |
        | OS1        | teacher1 | Test Folder Overview   | My Folder Overview Description 1  | module     | folder_content |
      And I follow "Test Open Studio name 1"
      And I follow "People" in the openstudio navigation
      And I follow "Shared Content > My Module" in the openstudio navigation
      And the "src" attribute of "img.openstudio-default-folder-img" "css_element" should contain "uploads_rgb_32px"
      And I follow "Test Folder Overview"
      And I follow "Add new content"
      And I press "Add file"
      And I set the following fields to these values:
        | Title                     | Test My Folder Overview                    |
        | Description               | My Folder Overview Description             |
        | Files                     | mod/openstudio/tests/importfiles/test1.jpg |
      And I press "Save"

      # Redirect to content detail
      And I should see "Test My Folder Overview"
      And the "src" attribute of "img.openstudio-content-view-img" "css_element" should contain "test1.jpg"
      And I follow "Shared Content > My Module" in the openstudio navigation
      And the "src" attribute of "img.openstudio-content-folder-img" "css_element" should contain "test1.jpg"

      # Content Upload should exist in Folder Overview
      And I follow "Test Folder Overview"
      And I should see "Test My Folder Overview"
      And the "src" attribute of "div.openstudio-grid-item-folder-preview > a > img" "css_element" should contain "test1.jpg"

  @_file_upload
  Scenario: Folder Overview in My Activities views
      Given the following open studio "level1s" exist:
          | openstudio  | name         | sortorder |
          | OS1         | Block1       | 1         |
      And the following open studio "level2s" exist:
          | level1      | name         | sortorder |
          | Block1      | Activity1    | 1         |
      And the following open studio "level3s" exist:
          | level2      | name         | sortorder | contenttype    |
          | Activity1   | Content1.1   | 1         | folder         |
      And I follow "Test Open Studio name 1"
      And I follow "My Content > My Activities" in the openstudio navigation
      And I should see "Content1.1"
      And the "src" attribute of "img.openstudio-default-folder-img" "css_element" should contain "uploads_rgb_32px"
      And I click on "Content1.1" "link" in the ".openstudio-grid-item" "css_element"
      # Folder title empty
      And I should see "Block1 - Activity1 - Content1.1"

      # Redirect to folder overview page
      And I follow "Add new content"
      And I press "Add file"
      And I set the following fields to these values:
        | Title                     | Test My Folder Overview                    |
        | Description               | My Folder Overview Description             |
        | Files                     | mod/openstudio/tests/importfiles/test1.jpg |
      And I press "Save"

      # Redirect to content detail
      And I should see "Test My Folder Overview"
      And the "src" attribute of "img.openstudio-content-view-img" "css_element" should contain "test1.jpg"
      And I follow "Edit folder details and sharing"
      And I set the following fields to these values:
        | Who can view this folder  | My module                                  |
        | Folder title              | Test my folder view 1                      |
        | Folder description        | My folder view description 1               |
      And I press "Save"
      # Folder Title has value
      And I should see "Test my folder view 1"
      And I follow "Shared Content > My Module" in the openstudio navigation
      And the "src" attribute of "img.openstudio-content-folder-img" "css_element" should contain "test1.jpg"

      And I follow "My Content > My Activities" in the openstudio navigation
      And the "src" attribute of "img.openstudio-content-folder-img" "css_element" should contain "test1.jpg"
      And I should see "Content1.1"

      # Content Upload should exist in Folder Overview
      And I click on "img.openstudio-content-folder-img" "css_element"
      And I should see "Test My Folder Overview"
      And the "src" attribute of "div.openstudio-grid-item-folder-preview > a > img" "css_element" should contain "test1.jpg"

  Scenario: Select existing post in Folder Overview
      Given the following open studio "folders" exist:
        | openstudio | user     | name                   | description                       | visibility | contenttype    |
        | OS1        | teacher1 | Test Folder Overview   | My Folder Overview Description 1  | module     | folder_content |
      And the following open studio "contents" exist:
        | openstudio | user     | name                  | description                 | file                                               | visibility  | index | keyword |
        | OS1        | teacher1 | TestContentFolders 1  | Test content 1 description  | mod/openstudio/tests/importfiles/test1.jpg         | private     | 1     | folder  |
        | OS1        | student1 | TestContentFolders 2  | Test content 2 description  | mod/openstudio/tests/importfiles/test2.jpg         | module      | 2     | folder  |
        | OS1        | student2 | TestContentFolders 3  | Test content 3 description  | mod/openstudio/tests/importfiles/test3.jpg         | module      | 3     | folder  |
        | OS1        | student3 | TestContentFolders 4  | Test content 4 description  | mod/openstudio/tests/importfiles/test4.jpg         | module      | 4     | folder  |
    And the following config values are set as admin:
      | enableglobalsearch | 1 |                          |
      | modulesitesearch   | 2 | local_moodleglobalsearch |
      | activitysearch     | 2 | local_moodleglobalsearch |
      | nonosepsitesearch  | 1 | local_moodleglobalsearch |

      # Go to folder overview
      And I follow "Test Open Studio name 1"
      And I follow "Shared Content > My Module" in the openstudio navigation
      And I follow "Test Folder Overview"

      # Should not see student posts
      And I press "Select existing post to add to folder"
      And I set the field "openstudio_search_post" to "folder"
      And I click on "Search" "button" in the "Browse posts" "dialogue"
      And I should not see "TestContentFolders 2"
      And I should not see "TestContentFolders 3"
      And I should not see "TestContentFolders 4"

      # Enable Add any contents to folders
      And I am on "Course 1" course homepage
      And I follow "Test Open Studio name 1"
      And I navigate to "Edit settings" in current page administration
      And I follow "Expand all"
      And I set the field "Add any contents to folders" to "1"
      And I press "Save and display"

      # Go to folder overview
      And I follow "People" in the openstudio navigation
      And I follow "Shared Content > My Module" in the openstudio navigation
      And I follow "Test Folder Overview"
      And I should not see "TestContentFolders 1"

      # teacher1 most recent posts
      And I press "Select existing post to add to folder"
      And I should see "TestContentFolders 1"

      # Select content of teacher1 to folder
      And I click on "Select" "button" in the "Browse posts" "dialogue"
      And I click on "Save changes" "button" in the "Browse posts" "dialogue"
      And I should see "TestContentFolders 1"

      # Select content of student1 to folder
      And I press "Select existing post to add to folder"
      And I set the field "openstudio_search_post" to "folder"
      And I click on "Search" "button" in the "Browse posts" "dialogue"
      And I should see "TestContentFolders 2"
      And I select the existing openstudio post "TestContentFolders 2"
      And I click on "Save changes" "button" in the "Browse posts" "dialogue"
      And I should see "TestContentFolders 2"

      # Select content of student2 to folder
      And I press "Select existing post to add to folder"
      And I set the field "openstudio_search_post" to "folder"
      And I click on "Search" "button" in the "Browse posts" "dialogue"
      And I should see "TestContentFolders 3"
      And I select the existing openstudio post "TestContentFolders 3"
      And I click on "Save changes" "button" in the "Browse posts" "dialogue"
      And I should see "TestContentFolders 3"

  Scenario: Order Posts in Folder Overview
      Given the following open studio "folders" exist:
        | openstudio | user     | name                   | description                       | visibility | contenttype    |
        | OS1        | teacher1 | Test Folder Overview   | My Folder Overview Description 1  | module     | folder_content |

      # Add new content
      And the following open studio "contents" exist:
        | openstudio | user     | name                          | description                    | visibility   |
        | OS1        | teacher1 | Test My Content Folder View 1 | Test My Content Details View 1 | infolderonly |
        | OS1        | teacher1 | Test My Content Folder View 2 | Test My Content Details View 2 | infolderonly |
        | OS1        | teacher1 | Test My Content Folder View 3 | Test My Content Details View 3 | infolderonly |

      # Add content to folder
      And the following open studio "folder contents" exist:
        | openstudio | user     | folder                | content                       |
        | OS1        | teacher1 | Test Folder Overview  | Test My Content Folder View 1 |
        | OS1        | teacher1 | Test Folder Overview  | Test My Content Folder View 2 |
        | OS1        | teacher1 | Test Folder Overview  | Test My Content Folder View 3 |

      And I follow "Test Open Studio name 1"
      And I follow "People" in the openstudio navigation
      And I follow "Shared content > My Module" in the openstudio navigation
      And I follow "Test Folder Overview"

      # Content position before order
      And Open studio contents should be in the following order:
        | Test My Content Folder View 1 |
        | Test My Content Folder View 2 |
        | Test My Content Folder View 3 |

      And I press "Order posts"
      And I click on ".openstudio-orderpost-item-movedown-button img" "css_element"
      And I click on "Save order" "button" in the "Order posts" "dialogue"

      # Update position content
      And Open studio contents should be in the following order:
        | Test My Content Folder View 2 |
        | Test My Content Folder View 1 |
        | Test My Content Folder View 3 |

      And I press "Order posts"
      And I set the field "Move to post number" to "3"
      And I click on "Save order" "button" in the "Order posts" "dialogue"

      # Update position content
      And Open studio contents should be in the following order:
        | Test My Content Folder View 1 |
        | Test My Content Folder View 3 |
        | Test My Content Folder View 2 |

      # Check title attribute of button
      And I press "Order posts"
      When I click on ".openstudio-orderpost-item-movedown-button img" "css_element"
      Then ".openstudio-orderpost-item:nth-child(1) .openstudio-orderpost-item-movedown-button[title='Move content Test My Content Folder View 3 down to position 2.']" "css_element" should exist
      And ".openstudio-orderpost-item:nth-child(2) .openstudio-orderpost-item-moveup-button[title='Move content Test My Content Folder View 1 up to position 1.']" "css_element" should exist
      And ".openstudio-orderpost-item:nth-child(2) .openstudio-orderpost-item-movedown-button[title='Move content Test My Content Folder View 1 down to position 3.']" "css_element" should exist

  @_file_upload
  Scenario: Content of order posts in folder overview
      Given the following open studio "folders" exist:
        | openstudio | user     | name                   | description                       | visibility | contenttype    |
        | OS1        | teacher1 | Test Folder Overview   | My Folder Overview Description 1  | module     | folder_content |

      # Add new content
      And the following open studio "contents" exist:
        | openstudio | user     | name                          | description                    | file                                              | visibility   |
        | OS1        | teacher1 | Test My Content Folder View 1 | Test My Content Details View 1 | mod/openstudio/tests/importfiles/test1.jpg        | infolderonly |

      # Add content to folder
      And the following open studio "folder contents" exist:
        | openstudio | user     | folder                | content                       |
        | OS1        | teacher1 | Test Folder Overview  | Test My Content Folder View 1 |

      And I follow "Test Open Studio name 1"
      And I follow "People" in the openstudio navigation
      And I follow "Shared content > My Module" in the openstudio navigation
      And I follow "Test Folder Overview"
      And I press "Order posts"

      # Only once content Move Up and Move Down Button disable
      And the "Move Up" "button" should be disabled
      And the "Move Down" "button" should be disabled
      And I wait until the page is ready
      And I click on "Close" "button" in the "Order posts" "dialogue"
      And I follow "Add new content"
      And I press "Add file"
      And I set the following fields to these values:
        | Title                     | Test My Folder Overview                    |
        | Description               | My Folder Overview Description             |
        | Files                     | mod/openstudio/tests/importfiles/test1.jpg |
      And I press "Save"
      And I follow "Shared content > My Module" in the openstudio navigation
      And I follow "Test Folder Overview"
      And I press "Order posts"
      # First content should disable Move Up button
      And the "Move Up" "button" should be disabled
      And the "Move Down" "button" should be enabled

  @_file_upload
  Scenario: Folder Overview in My Activities with item can not be reordered
      Given the following open studio "level1s" exist:
          | openstudio  | name         | sortorder |
          | OS1         | Block1       | 1         |
      And the following open studio "level2s" exist:
          | level1      | name         | sortorder |
          | Block1      | Activity1    | 1         |
      And the following open studio "level3s" exist:
          | level2      | name         | sortorder | contenttype    |
          | Activity1   | Content1.1   | 1         | folder         |
      And the following open studio "folder templates" exist:
            | level3         | additionalcontents  |
            | Content1.1     | 10                  |
      # Add 2 content can not be reordered
      And the following open studio "folder content templates" exist:
            | level3         | name       | contentpreventreorder |
            | Content1.1     | Content 1  | 1                     |
            | Content1.1     | Content 2  | 1                     |
      And I am on "Course 1" course homepage
      And I follow "Test Open Studio name 1"
      And I follow "My Content > My Activities" in the openstudio navigation
      And I click on "Content1.1" "link" in the ".openstudio-grid-item" "css_element"

      # Redirect to content detail
      And I follow "Content 1"
      And I press "Add file"
      And I set the following fields to these values:
        | Title                     | Test My Folder Overview 1                  |
        | Description               | My Folder Overview Description 1           |
        | Files                     | mod/openstudio/tests/importfiles/test1.jpg |
      And I press "Save"
      And I follow "My Content > My Activities" in the openstudio navigation
      And I click on "a.openstudio-profile-mypaticipation" "css_element"
      And I follow "Content1.1"
      And I follow "Content 2"
      And I press "Add file"
      And I set the following fields to these values:
        | Title                     | Test My Folder Overview 2                  |
        | Description               | My Folder Overview Description 2           |
        | Files                     | mod/openstudio/tests/importfiles/test2.jpg |
      And I press "Save"
      And I follow "My Content > My Activities" in the openstudio navigation
      And I click on "a.openstudio-profile-mypaticipation" "css_element"
      And I follow "Content1.1"
      And I should not see "Order posts"

      # Content can re-order
      And I follow "Add new content"
      And I press "Add file"
      And I set the following fields to these values:
        | Title                     | Test My Folder Overview 3                  |
        | Description               | My Folder Overview Description 3           |
        | Files                     | mod/openstudio/tests/importfiles/test1.jpg |
      And I press "Save"
      And I follow "My Content > My Activities" in the openstudio navigation
      And I click on "a.openstudio-profile-mypaticipation" "css_element"
      And I follow "Content1.1"
      And Open studio contents should be in the following order:
        | Test My Folder Overview 1 |
        | Test My Folder Overview 2 |
        | Test My Folder Overview 3 |
      And I press "Order posts"
      # User cannot input number to order fixed content, error message will display
      And I click on "#openstudio-folder-reorder-down-0" "css_element"
      And I should see "You cannot move this content beyond other fixed contents."
      # User can click button to re-order content
      And I click on "#openstudio-folder-reorder-up-2" "css_element"
      And I click on "Save order" "button" in the "Order posts" "dialogue"
      And Open studio contents should be in the following order:
        | Test My Folder Overview 1 |
        | Test My Folder Overview 3 |
        | Test My Folder Overview 2 |

      And I follow "Add new content"
      And I press "Add file"
      And I set the following fields to these values:
        | Title          | Test My Folder Overview 4                  |
        | Description    | My Folder Overview Description 4           |
        | Files          | mod/openstudio/tests/importfiles/test1.jpg |
      And I press "Save"
      And I follow "My Content > My Activities" in the openstudio navigation
      And I click on "a.openstudio-profile-mypaticipation" "css_element"
      And I follow "Content1.1"
      And I press "Order posts"
      And I set the field "Move to post number" to "4"
      And I click on "Save order" "button" in the "Order posts" "dialogue"
      And I should see "You cannot move this content beyond other fixed contents."
      And I wait until the page is ready
      And I click on "Close" "button" in the "Order posts" "dialogue"
      And Open studio contents should be in the following order:
        | Test My Folder Overview 1 |
        | Test My Folder Overview 3 |
        | Test My Folder Overview 2 |
        | Test My Folder Overview 4 |

      # User cannot input a zero number
      And I press "Order posts"
      And I set the field "Move to post number" to "0"
      Then I should see "You cannot enter a position that does not contain a slot."

      # Lose input focus
      And I set the field "Move to post number" to "4"
      And I click on "Move to post number" "text"
      Then I should see "You cannot move this content beyond other fixed contents."

      And I wait until the page is ready
      And I click on "Close" "button" in the "Order posts" "dialogue"
      And I press "Order posts"

      # User can move content can re-order to 1
      And I set the field with xpath "//input[@id='openstudio-folder-reorder-input-4']" to "1"
      And I click on "Move to post number" "text"

      # User cannot input number to order fixed content, error message will display
      And I set the field with xpath "//input[@id='openstudio-folder-reorder-input-3']" to "1"
      And I click on "Move to post number" "text"
      And I should see "You cannot move this content beyond other fixed contents."

      # Multiply movement content
      And I wait until the page is ready
      And I click on "Close" "button" in the "Order posts" "dialogue"
      And I press "Order posts"
      And I set the field with xpath "//input[@id='openstudio-folder-reorder-input-4']" to "3"
      And I click on "Move to post number" "text"
      And I set the field with xpath "//input[@id='openstudio-folder-reorder-input-4']" to "2"
      And I click on "Move to post number" "text"
      And I wait until the page is ready
      And I click on "Save order" "button" in the "Order posts" "dialogue"
      And Open studio contents should be in the following order:
        | Test My Folder Overview 1 |
        | Test My Folder Overview 4 |
        | Test My Folder Overview 3 |
        | Test My Folder Overview 2 |

    @_file_upload
    Scenario: Folder Overview in My Activities with Zero ordering

      # Add 3 booked slot on Folder Activity
      Given the following open studio "level1s" exist:
          | openstudio  | name         | sortorder |
          | OS1         | Block1       | 1         |
      And the following open studio "level2s" exist:
          | level1      | name         | sortorder |
          | Block1      | Activity1    | 1         |
      And the following open studio "level3s" exist:
          | level2      | name         | sortorder | contenttype    |
          | Activity1   | Content1.1   | 1         | folder         |
      And the following open studio "folder templates" exist:
            | level3         | additionalcontents  |
            | Content1.1     | 10                  |
      # Add 2 content can not be reordered
      And the following open studio "folder content templates" exist:
            | level3         | name       | contentpreventreorder |
            | Content1.1     | Content 1  | 1                     |
            | Content1.1     | Content 2  | 1                     |
            | Content1.1     | Content 3  | 1                     |
      And I am on "Course 1" course homepage
      And I follow "Test Open Studio name 1"
      And I follow "My Content > My Activities" in the openstudio navigation
      And I click on "Content1.1" "link" in the ".openstudio-grid-item" "css_element"

      # Upload a new content
      And I follow "Add new content"
      And I press "Add file"
      And I set the following fields to these values:
        | Title                     | Content 4                                  |
        | Description               | My Folder Overview Description             |
        | Files                     | mod/openstudio/tests/importfiles/test1.jpg |
      And I press "Save"
      And I follow "My Content > My Activities" in the openstudio navigation
      And I click on "a.openstudio-profile-mypaticipation" "css_element"
      And I follow "Content1.1"

      # Order Post
      And I press "Order posts"
      And "Content 3" "text" should appear before "Content 4" "text"

      # Entering 0 as the position of the last item
      And I set the field with xpath "//input[@id='openstudio-folder-reorder-input-4']" to "0"
      And I click on "Move to post number" "text"

      # Validation message raises
      And I should see "You cannot enter a position that does not contain a slot."

      # Content won't change order
      And "Content 3" "text" should appear before "Content 4" "text"

    Scenario: Remove last selection Item Button work correctly

      Given the following open studio "folders" exist:
        | openstudio | user     | name                   | description                       | visibility | contenttype    |
        | OS1        | teacher1 | Test Folder Overview   | My Folder Overview Description 1  | module     | folder_content |
      And the following open studio "contents" exist:
        | openstudio | user     | name                  | description                 | file                                               | visibility  |
        | OS1        | teacher1 | TestRemove 1          | Test content 1 description  | mod/openstudio/tests/importfiles/test3.jpg         | module      |
        | OS1        | teacher1 | TestRemove 2          | Test content 2 description  | mod/openstudio/tests/importfiles/test2.jpg         | module      |
        | OS1        | teacher1 | TestRemove 3          | Test content 2 description  | mod/openstudio/tests/importfiles/test1.jpg         | module      |
      And I follow "Test Open Studio name 1"
      And I follow "My Content"
      And I follow "Test Folder Overview"
      And I press "Select existing post to add to folder"
      And I should see "TestRemove 1"
      And I should see "TestRemove 2"
      And I should see "TestRemove 3"
      # Add 1st Item
      And I select the existing openstudio post "TestRemove 1"
      And I should not see "TestRemove 1"
      # Add 2nd Item
      And I select the existing openstudio post "TestRemove 2"
      And I should not see "TestRemove 2"
      # Add 3rd Item
      And I select the existing openstudio post "TestRemove 3"
      And I should not see "TestRemove 3"
      # Observe the result
      And I click on "Remove last selection" "button" in the "Browse posts" "dialogue"
      And I should see "TestRemove 3"
      And I should not see "TestRemove 2"
      And I should not see "TestRemove 1"

  Scenario: Activity guidance in folder overview
      Given the following open studio "level1s" exist:
        | openstudio | name   | sortorder |
        | OS1        | Block1 | 1         |
      And the following open studio "level2s" exist:
        | level1 | name      | sortorder |
        | Block1 | Activity1 | 1         |
      And the following open studio "level3s" exist:
        | level2    | name     | sortorder | contenttype |
        | Activity1 | Folder 1 | 1         | folder      |
      And the following open studio "folder templates" exist:
        | level3   | additionalcontents | guidance             |
        | Folder 1 | 10                 | Folder guidance text |
      And the following open studio "folder content templates" exist:
        | level3   | name      | guidance           |
        | Folder 1 | Content 1 | Content 1 guidance |
        | Folder 1 | Content 2 |                    |
      And I am on "Course 1" course homepage
      And I follow "Test Open Studio name 1"
      And I follow "My Content > My Activities" in the openstudio navigation
      And I click on "Folder 1" "link" in the ".openstudio-grid-item" "css_element"
      Then I press "Activity guidance"
      And I should see "Folder guidance text" in the ".modal-body" "css_element"
      And I should see "Content 1" in the ".modal-body" "css_element"
      And I should see "Content 1 guidance" in the ".modal-body" "css_element"
      And I should see "Content 2" in the ".modal-body" "css_element"
      And I should see "No guidance has been given for this content" in the ".modal-body" "css_element"

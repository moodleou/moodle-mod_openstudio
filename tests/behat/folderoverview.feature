@ou @ou_vle @mod @mod_openstudio @mod_openstudio_folder_overview @javascript
Feature: Folder Overview
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
      And I log in as "teacher1"
      And I am on site homepage
      And I follow "Course 1"
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
      And I change viewport size to "large"

  Scenario: Check Item Folder Overview
      Given the following open studio "folders" exist:
        | openstudio | user     | name                   | description                       | visibility | contenttype    |
        | OS1        | teacher1 | Test Folder Overview   | My Folder Overview Description 1  | module     | folder_content |
      And I follow "Test Open Studio name 1"
      And I follow "Shared Content > My Module" in the openstudio navigation
      And I follow "Test Folder Overview"
      And I should see "Folder Overview"

      # the left handside should be the post stream
      And I should see "Test Folder Overview"
      And I should see "Edit folder title and permissions"

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
      And I press "Folder Comments"
      And I should see "Folder Comments"
      And "Add new comment" "button" should exist

      And "Delete folder" "button" should exist
      And "Lock folder" "button" should exist
      And "Request feedback" "button" should exist

      And I should see "0 Favourites"
      And I should see "0 Smiles"
      And I should see "0 Inspired"
      And I should see "0 views"

  Scenario: Upload content in Folder Overview
      Given the following open studio "folders" exist:
        | openstudio | user     | name                   | description                       | visibility | contenttype    |
        | OS1        | teacher1 | Test Folder Overview   | My Folder Overview Description 1  | module     | folder_content |
      And I follow "Test Open Studio name 1"
      And I follow "Shared Content > My Module" in the openstudio navigation
      And the "src" attribute of "img.openstudio-default-folder-img" "css_element" should contain "uploads_rgb_32px"
      And I follow "Test Folder Overview"
      And I follow "Add new content"
      And I press "Add file"
      And I set the following fields to these values:
        | Title                     | Test My Folder Overview                    |
        | Description               | My Folder Overview Description             |
        | Upload content            | mod/openstudio/tests/importfiles/test1.jpg |
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
      And I follow "Content1.1"
      # Redirect to create folder page
      And I set the following fields to these values:
          | Who can view this folder  | My module                                  |
          | Folder title              | Test my folder view 1                      |
          | Folder description        | My folder view description 1               |
      And I press "Create folder"

      # Redirect to folder overview page
      And I follow "Add new content"
      And I press "Add file"
      And I set the following fields to these values:
        | Title                     | Test My Folder Overview                    |
        | Description               | My Folder Overview Description             |
        | Upload content            | mod/openstudio/tests/importfiles/test1.jpg |
      And I press "Save"

      # Redirect to content detail
      And I should see "Test My Folder Overview"
      And the "src" attribute of "img.openstudio-content-view-img" "css_element" should contain "test1.jpg"
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
        | openstudio | user     | name                  | description                 | file                                               | visibility  |
        | OS1        | teacher1 | TestContentFolders 1  | Test content 1 description  | mod/openstudio/tests/importfiles/test1.jpg         | private     |
        | OS1        | student1 | TestContentFolders 2  | Test content 2 description  | mod/openstudio/tests/importfiles/test2.jpg         | module      |
        | OS1        | student2 | TestContentFolders 3  | Test content 3 description  | mod/openstudio/tests/importfiles/test3.jpg         | module      |
        | OS1        | student3 | TestContentFolders 4  | Test content 4 description  | mod/openstudio/tests/importfiles/test4.jpg         | module      |

      # Enable Add any contents to folders
      And I follow "Test Open Studio name 1"
      And I follow "Administration > Edit" in the openstudio navigation
      And I follow "Expand all"
      And I set the field "Add any contents to folders" to "1"
      And I press "Save and display"

      # Go to folder overview
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
      And I set the field "search" to "TestContentFolders 2"
      And I press "Search"
      And I should see "TestContentFolders 2"
      And I click on "Select" "button" in the "Browse posts" "dialogue"
      And I click on "Save changes" "button" in the "Browse posts" "dialogue"
      And I should see "TestContentFolders 2"

      # Select content of student2 to folder
      And I press "Select existing post to add to folder"
      And I set the field "search" to "TestContentFolders 3"
      And I press "Search"
      And I should see "TestContentFolders 3"
      And I click on "Select" "button" in the "Browse posts" "dialogue"
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
      And I follow "Shared content > My Module" in the openstudio navigation
      And I follow "Test Folder Overview"
      And I press "Order posts"

      # Only once content Move Up and Move Down Button disable
      And the "Move Up" "button" should be disabled
      And the "Move Down" "button" should be disabled

      And I click on "Close" "button" in the "Order posts" "dialogue"
      And I follow "Add new content"
      And I press "Add file"
      And I set the following fields to these values:
        | Title                     | Test My Folder Overview                    |
        | Description               | My Folder Overview Description             |
        | Upload content            | mod/openstudio/tests/importfiles/test1.jpg |
      And I press "Save"
      And I follow "Shared content > My Module" in the openstudio navigation
      And I follow "Test Folder Overview"
      And I press "Order posts"
      # First content should disable Move Up button
      And the "Move Up" "button" should be disabled
      And the "Move Down" "button" should be enabled

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
      And I follow "Course 1"
      And I follow "Test Open Studio name 1"
      And I follow "My Content > My Activities" in the openstudio navigation
      And I follow "Content1.1"

      # Redirect to  add folder page
      And I set the following fields to these values:
        | Who can view this folder  | My module                                  |
        | Folder title              | Test my folder view 1                      |
        | Folder description        | My folder view description 1               |
      And I press "Create folder"
      And I follow "Content 1"
      And I press "Add file"
      And I set the following fields to these values:
        | Title                     | Test My Folder Overview 1                  |
        | Description               | My Folder Overview Description 1           |
        | Upload content            | mod/openstudio/tests/importfiles/test1.jpg |
      And I press "Save"
      And I follow "My Content > My Activities" in the openstudio navigation
      And I follow "Content1.1"
      And I follow "Content 2"
      And I press "Add file"
      And I set the following fields to these values:
        | Title                     | Test My Folder Overview 2                  |
        | Description               | My Folder Overview Description 2           |
        | Upload content            | mod/openstudio/tests/importfiles/test2.jpg |
      And I press "Save"
      And I follow "My Content > My Activities" in the openstudio navigation
      And I follow "Content1.1"
      And I press "Order posts"

      # Content can not re-order. User click down button to move pass another fixed content, error message will display
      And the "Save order" "button" should be disabled
      And the "Move Up" "button" should be disabled
      And I click on "#openstudio-folder-reorder-down-0" "css_element"
      And I should see "You cannot move this content beyond other fixed contents."
      
      # User cannot input number to order fixed content, error message will display
      And I set the field "Move to post number" to "2"
      And I click on "Save order" "button" in the "Order posts" "dialogue"
      And I should see "You cannot move this content beyond other fixed contents."

      # Content can re-order
      And I click on "Close" "button" in the "Order posts" "dialogue"
      And I follow "Add new content"
      And I press "Add file"
      And I set the following fields to these values:
        | Title                     | Test My Folder Overview 3                  |
        | Description               | My Folder Overview Description 3           |
        | Upload content            | mod/openstudio/tests/importfiles/test1.jpg |
      And I press "Save"
      And I follow "My Content > My Activities" in the openstudio navigation
      And I follow "Content1.1"
      And I press "Order posts"
      # User cannot input number to order fixed content, error message will display
      And I click on "#openstudio-folder-reorder-down-0" "css_element"
      And I should see "You cannot move this content beyond other fixed contents."
      # User can click button to re-order content
      And I click on "#openstudio-folder-reorder-up-2" "css_element"
      And I click on "Save order" "button" in the "Order posts" "dialogue"
      And Open studio contents should be in the following order:
        | Test My Folder Overview 2 |
        | Test My Folder Overview 1 |
        | Test My Folder Overview 3 |

      And I follow "Add new content"
      And I press "Add file"
      And I set the following fields to these values:
        | Title          | Test My Folder Overview 4                  |
        | Description    | My Folder Overview Description 4           |
        | Upload content | mod/openstudio/tests/importfiles/test1.jpg |
      And I press "Save"
      And I follow "My Content > My Activities" in the openstudio navigation
      And I follow "Content1.1"
      And I press "Order posts"
      And I set the field "Move to post number" to "4"
      And I click on "Save order" "button" in the "Order posts" "dialogue"
      And I should see "You cannot move this content beyond other fixed contents."
      And I click on "Close" "button" in the "Order posts" "dialogue"
      And Open studio contents should be in the following order:
        | Test My Folder Overview 2 |
        | Test My Folder Overview 1 |
        | Test My Folder Overview 3 |
        | Test My Folder Overview 4 |

      # User cannot input a zero number
      And I press "Order posts"
      And I set the field "Move to post number" to "0"
      Then I should see "You cannot enter a position that does not contain a slot."

        # Lose input focus
      And I set the field "Move to post number" to "4"
      And I click on "Move to post number" "text"
      Then I should see "You cannot move this content beyond other fixed contents."

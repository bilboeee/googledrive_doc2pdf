<?php


class GoogleDriveApi {
	//Initializing the variables for the API
	//Login with the service account
	//Check https://developers.google.com/drive/service-accounts
	private $_clientID 				= '';
	private $_serviceAccountName 	= '';
	private $_keyFile 				= '';
	private $_scope 				= '';
	private $_localPath 			= '';
	private $_localAdminPath 		= '';

	private $_currentFile 			= NULL;
	private $_googleService 		= NULL;
	private static $_instance 		= NULL;


	/**
    * Class Constructor
    *
    * @param void
    * @return void
    */
	private function __construct() {

		//Including files for the api
		require_once "./google-api-php-client/src/Google_Client.php";
		require_once "./google-api-php-client/src/contrib/Google_DriveService.php";
		require_once "./google-api-php-client/src/contrib/Google_Oauth2Service.php";

	    //Initializing the service
		$this->_googleService = $this->_buildService();
	}



   /**
    * If the class exist we send the instance
    * if not, we create a new instance of the class
    *
    * @param void
    * @return Singleton
    */
	public static function getInstance() {
		if( is_null(self::$_instance) ){
			self::$_instance = new GoogleDriveApi();
		}

		return self::$_instance;
	}



	/**
	 * Build and returns a Drive service object authorized with the service accounts
	 * that acts on behalf of the given user.
	 *
	 * @return Google_DriveService service object.
	 *
	 * https://developers.google.com/drive/service-accounts
	 *
	*/
	private function _buildService() {
		//Get the content of the key file
		$key = file_get_contents( $this->_keyFile );

		//Prepare the credentials
		$auth = new Google_AssertionCredentials(
			$this->_serviceAccountName,
			array( $this->_scope ),
			$key
		);

		//Create the client
		$client = new Google_Client();

		//Init the client
		$client->setScopes( array( $this->_scope ) );
		$client->setUseObjects( true );

		//Set the credentials
		$client->setAssertionCredentials( $auth );

		//Auth the client
		$client->getAuth()->refreshTokenWithAssertion();
		$accessToken = $client->getAccessToken();
		$client->setClientId( $this->_clientID );

		//Return the service
		return new Google_DriveService( $client );
	}



	/**
	 * Create a new file and upload the file uploaded by the user
	 *
	 * @param Google_DriveService $service Drive API service instance.
	 * @param Array The file's uploaded by the user.
	 * @return Object The file's object if successful, null otherwise.
	 *
	 */
	private function _createFile( $fileuploaded ){
		//Create the file
		$file = new Google_DriveFile();

		//Set the file name
		$file->setTitle( jardiland_recrutement_urlEncode( $fileuploaded['name'] ) );

		//Set the Mime-type
		$file->setMimeType( $fileuploaded['type'] );

		//Create and upload the file
		$this->_currentFile = $this->_googleService->files->insert($file, array(
			'data' => file_get_contents($fileuploaded["tmp_name"]),
			'mimeType' => $fileuploaded['type'],
			'convert' => true,
		));

		//Set the public permission
		$this->_setPublicPermission();

		return $this->_currentFile;
	}
	public function createFile( $fileuploaded ){
		return $this->_createFile( $fileuploaded );
	}



	/**
	 * Set the public permission on the file
	 *
	 */
	 private function _setPublicPermission(){
		$permission = new Google_Permission();
		$permission->setRole( 'writer' );
		$permission->setType( 'anyone' );
		$permission->setValue( 'me' );
		$this->_googleService->permissions->insert( $this->_currentFile->getId(), $permission );
	 }





  	/**
	 * Get the link to download a file in exported format
	 *
	 * @param File $file Drive File instance.
	 * @param Type the mimy-type wanted.
	 * @return String The file's link.
	 *
	 *
	 */
	 private function _getFileExportLink( $file, $type ){
		 $links = $this->_currentFile->getExportLinks();

		 $link = $links[ $type ];

		 return $link;
	 }
	 public function getFileExportLink( $type ){
		 return $this->_getFileExportLink( $this->_googleService, $type );
	 }



  	/**
	 * Download a file's content.
	 *
	 * @param Google_DriveService $service Drive API service instance.
	 * @param File $file Drive File instance.
	 * @return String The file's content if successful, null otherwise.
	 *
	 * https://developers.google.com/drive/manage-downloads
	 *
	 */
	 private function _getFileContent( $service, $file ) {
		$downloadUrl = $file->getDownloadUrl();

		if ($downloadUrl) {
			$request = new Google_HttpRequest($downloadUrl, 'GET', null, null);
			$httpRequest = Google_Client::$io->authenticatedRequest($request);

			if ($httpRequest->getResponseHttpCode() == 200) {
				return $httpRequest->getResponseBody();
			} else {
				// An error occurred.
				return null;
			}
		} else {
			// The file doesn't have any content stored on Drive.
			return null;
		}
	}
	public function getFileContent(){
		$this->_getFileContent( $this->_googleService, $this->_currentFile );
	}



  	/**
	 * Permanently delete a file, skipping the trash.
	 *
	 * @param Google_DriveService $service Drive API service instance.
	 * @param String $fileId ID of the file to delete.
	 *
	 * https://developers.google.com/drive/v2/reference/files/delete
	 *
	 */
	private function _deleteFile($service, $fileId) {
		try {
			$service->files->delete($fileId);
		} catch (Exception $e) {
			echo "An error occurred: " . $e->getMessage();
		}
	}
	public function deleteFile(){
		$this->_deleteFile( $this->_googleService, $this->_currentFile->getId() );
	}




	/**
	 * Get the local path
	 *
	 * @return String The local path
	 *
	 */
	public function getLocalPath() {
		return $this->_localPath;
	}


}


?>
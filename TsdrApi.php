<?php
/**
* This API will allow users to create an AJAX form
* that takes a serial or registation number of a trademark and 
* get important information about it
* 
* @version 0.3 Beta
*/

include_once 'urlupload.php';

class TsdrApi
{
	const NO_INFO_FOUND = "None";
	
	const STATUS_ABANDONED = "Abandoned";
	
	const STATUS_CANCELLED = "Registration cancelled";

	const REGISTRATION_NUMBER = "rn";

	const SERIAL_NUMBER = "sn";
	
	/**
	* This variable stores all the "important" data of the mark
	*
	* @var Array
	*/
	private $_data;

	/**
	* Name of the directory to store xml files
	*
	* @var String
	*/
	private $_dir;

	/**
	* SimpleXMLElement Object that represents the entire trademark
	* with all of its information
	*
	* @var Object
	*/
	private $_trademark;


	/**
	* Retrieves Trademark information
	* 
	* @param String $number serial or registration number
        * @param String $type sn or rn for serial number or registration number
	* @return Array $data|Bool false
	*/
	public function	getTrademarkData($number, $type)
	{
		// Check if status archive exists
		$this->_makeStatusDir();
	
		// Clean number input
		$number = $this->_cleanInput($number);
		
		// Check if exception was thrown
		if(!$this->_getTrademark($number, $type))
		{
			return False;
		}
		
		$this->_mapImportantData($this->_trademark);
		return $this->_data;
	}

	/**
	* Removes all files in archive directory
	* This method should be called at EOD or 12 AM
	* 
	* This method should be called from crontab
	*
	* @param String $dir name of directory
	*/
	public function flushArchive($dir = 'status_archive')
	{
		// Recursively remove all files if subdirectories exist
		$path = getcwd() . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR;
		foreach (glob("{$path}*") as $file) 
		{
			if(is_dir($file)) 
			{
				if(is_dir_empty($file))
				{
					rmdir($file);
				}
				else 
				{
					flushArchive($file);	
				}
        	} 
        	else 
        	{
            	unlink($file);
        	}
		}
	}

	/*=================== UTILITIES ======================*/

	/**
	* Generates trademark Object via Serial or Registration number
	* If an xml file already exists with the corresponding serial number,
	* it will parse it from disk rather than obtain it from TSDR system
	* 
	* @param String $number serial or registration number
        * @param String $type sn or rn for serial number or registration number
	* @param String $dir directory name to store xml
	* @return Bool False if exception caught
	*/
	private function _getTrademark($number, $type)
	{
		// Check if status file exists
		if($type === self::REGISTRATION_NUMBER || !file_exists($this->_dir. DIRECTORY_SEPARATOR ."{$number}_status_st96.xml"))
		{
			// @TODO Make abstraction for $url
			$url = "https://tsdrsec.uspto.gov/ts/cd/casedocs/{$type}{$number}/zip-bundle-download?case=true&docs=&assignments=&prosecutionHistory=";
	
			try 
			{
				// @TODO decouple urluploader class and use dependency injection principles
				$upload = new UrlUpload($url, $this->_dir, $number);
				$upload->uploadFromUrl(); 
 
                                // might have to change $number  registration number search returns searial.zip
  	                        $number = $upload->getLocalname();
			}
			catch (Exception $e)
			{
				// Development
				// echo "ERROR: ". $e->getMessage()."</br>";
				// echo "<pre>";
				// echo print_r($e->getFile())."\n";
				// echo "Line: ".$e->getLine();
				// echo "</pre>";
				// Production 

				$label = ($type == self::SERIAL_NUMBER ? "serial" : "registration");
				echo "<div class=\"error\">Sorry, but the provided $label number, $number, was not found </br> Please make sure you inserted the correct serial or registration number</div>";
				return false;
			}
		}
		
		$xml = simplexml_load_file($this->_dir. DIRECTORY_SEPARATOR . "{$number}_status_st96.xml", NULL, NULL, 'ns2', True);

		$trademark = $xml->TrademarkTransactionBody->TransactionContentBag->TransactionData->TrademarkBag->Trademark;

		$this->_trademark = $trademark;

		return true;
	}

	/**
	* Maps specific data from Trademark object
	* for ease of use
	*
	* @param Object $trademark
	*/
	private function _mapImportantData($trademark)
	{
		$counter = 0;
		$data = array();

		$data['ApplicationFilingDate'] 	= $trademark->ApplicationDate
											? $trademark->ApplicationDate: self::NO_INFO_FOUND;
		$ns1 = $trademark->children('ns1', true);

		$data['SerialNumber'] = $ns1->ApplicationNumber->ApplicationNumberText ? $ns1->ApplicationNumber->ApplicationNumberText: self::NO_INFO_FOUND;

		$data['RegistrationDate'] 	= $ns1->RegistrationDate
											? $ns1->RegistrationDate: self::NO_INFO_FOUND;
		$data['RegistrationNumber'] 	= $ns1->RegistrationNumber
											? $ns1->RegistrationNumber: self::NO_INFO_FOUND;								

		$data['RegistrationCertificate']	= $ns1->RegistrationNumber
											? ' <a target="_blank" href="https://tsdrsec.uspto.gov/ts/cd/casedocs/bundle.pdf?rn=' . $ns1->RegistrationNumber . '&amp;category=RC">Registration Certificate</a>': '';

		$data['MarkType'] 				= $trademark->MarkCategory
											? $trademark->MarkCategory: self::NO_INFO_FOUND;
											
		$data['Status']					= $trademark->NationalTrademarkInformation->MarkCurrentStatusExternalDescriptionText
											? $trademark->NationalTrademarkInformation->MarkCurrentStatusExternalDescriptionText: self::NO_INFO_FOUND;
		// Special logic for status									
		$data['Status'] = $this->_cleanUpStatus($data['Status']);
											
		$data['RenewalDate']			= $trademark->NationalTrademarkInformation->RenewalDate
											? $trademark->NationalTrademarkInformation->RenewalDate: self::NO_INFO_FOUND;
											
		$data['MarkLiteralElements']	= $trademark->MarkRepresentation->MarkReproduction->WordMarkSpecification->MarkVerbalElementText
											? $trademark->MarkRepresentation->MarkReproduction->WordMarkSpecification->MarkVerbalElementText: self::NO_INFO_FOUND;
											
		$data['StandardCharacterClaim']	= $trademark->MarkRepresentation->MarkReproduction->WordMarkSpecification->MarkStandardCharacterIndicator
											? $trademark->MarkRepresentation->MarkReproduction->WordMarkSpecification->MarkStandardCharacterIndicator: self::NO_INFO_FOUND;
		
		// Accounts for multiple classes and descriptions in a trademark 
		foreach ($trademark->GoodsServicesBag->GoodsServices as $GoodsServices) 
		{
			
			$data['ClassNumber'][$counter]			= $GoodsServices[$counter]->ClassDescriptionBag->ClassDescription[0]->ClassNumber
														? $GoodsServices[$counter]->ClassDescriptionBag->ClassDescription[0]->ClassNumber: self::NO_INFO_FOUND;
														
			$data['ClassDescription'][$counter]		= $GoodsServices[$counter]->ClassDescriptionBag->ClassDescription[0]->GoodsServicesDescriptionText
														? $GoodsServices[$counter]->ClassDescriptionBag->ClassDescription[0]->GoodsServicesDescriptionText: self::NO_INFO_FOUND;
			$counter++;
		}

		$this->_data = $data;
	}

	/**
	* Returns directory name and creates
	* a path if it doesn't exist
	* 
	*/
	private function _makeStatusDir()
	{
		$dir = 'status_archive';

		if (!file_exists($dir) && !is_dir($dir)) 
		{
	    	mkdir($dir);         
		}
		$this->_dir = $dir;
	}

	/**
	* Clean up user input for security purposes
	* 
	* @param String $data
	* @return String $data cleaned up user input
	*/
	private function _cleanInput($userInput) {
	  $userInput = trim($userInput);
	  $userInput = stripslashes($userInput);
	  $userInput = htmlspecialchars($userInput);
	  $userInput = str_replace(",", "", $userInput);
	  return $userInput;
	}
	
	
	/**
	 * Removes indication of TSDR website for applications
	 *
	 * @param String $status
	 */
	private function _cleanUpStatus($status)
	{
		// @TODO find a more elegent solution to substitute typecast
		$status = (string)$status;
		
		if($status === self::NO_INFO_FOUND)
		{
			return $status; // No modifications on status
		}
		
		// Check for status
		$arr = explode(' ',trim($status));
		
		if ($arr[0] == 'Registration')
		{
			$arr = $arr[0]." ".$arr[1];
		}
		else 
		{
			$arr = $arr[0];	
		}
				
		switch ($arr)
		{
			case self::STATUS_ABANDONED:
				$status = explode(". ", $status);
				$status = $status[0];
				return $status;
			case self::STATUS_CANCELLED:
				$status = explode(". ", $status);
				$status = $status[0];
				return $status;
			default:
				return $status; // No modifications on status
		}
		
		
		 	
	}
	
}
?>
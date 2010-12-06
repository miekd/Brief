<?php

/**
 * Validate
 **/
class Validate extends Scaffold_Module
{
	public static function check()
	{
		if(CSScaffold::config('core.in_production') !== true && CSScaffold::config('core.output') == "validate")
		{
			# Clean it up so we can use the line numbers
			CSS::pretty();
			
			# Get the validator options from the config
			$validator_options = CSScaffold::config('Validate');
			
			# Add our options
			$validator_options['text'] = CSS::$css;
			$validator_options['output'] = 'soap12';
			
			# Encode them
			$validator_options = http_build_query($validator_options);
			
			$url = "http://jigsaw.w3.org/css-validator/validator?$validator_options";
			
			# The Curl options
			$options = array
			(
				CURLOPT_URL 			=> $url,
				CURLOPT_RETURNTRANSFER 	=> 1,
			);
			
			# Start CURL
			$handle = curl_init();
			
			# Set the CURL options
			curl_setopt_array($handle, $options);
			
			# Store the response in a buffer
			$buffer = curl_exec($handle);
			
			# Close it
			curl_close($handle);
			
			# If something was returned
			if (!empty($buffer))
			{
				# Simplexml doesn't like colons
				$buffer = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $buffer);
				
				# Let it be xml!
				$results = simplexml_load_string($buffer);
				
				# Is it valid?
				$is_valid = (string)$results->envBody->mcssvalidationresponse->mvalidity;
				
				# Oh noes! Display the errors
				if($is_valid == "false")
				{
					# Lets get the errors into a nice array
					$errors = $results->envBody->mcssvalidationresponse->mresult->merrors;
					
					# Number of errors
					$count = (int)$errors->merrorcount;
					
					# Start creating the output message
					$message = "<ol>";
					
					foreach($errors->merrorlist->merror as $key => $error)
					{
						$message .= "<li><strong>". trim((string)$error->mmessage) . "</strong> line " . (string)$error->mline . " near " . (string)$error->mcontext . "</li>";
					}
					
					$message .= "</ol>";
					
					# Throw an error
					throw new Scaffold_Exception("CSS is not valid - $count errors" . $message);
				}
			}
		}
	}
} 

<?php
/**
 * $Id: TpWizardForm.php 128 2007-01-16 00:20:35Z rdg $
 * 
 * LICENSE INFORMATION
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details:
 * 
 * http://www.gnu.org/copyleft/gpl.html
 * 
 * 
 * @author Renato De Giovanni <renato [at] cria . org . br>
 */

require_once('TpPage.php');
require_once('TpUtils.php');
require_once('TpDiagnostics.php');

class TpWizardForm extends TpPage
{
    var $mWizardMode;
    var $mNumSteps = 6;
    var $mStep; // to be defined by subclasses
    var $mLabel = 'unlabelled step';
    var $mDone = false;
    var $mResource;

    function TpWizardForm( )
    {

    } // end of member function TpWizardForm

    function Initialize( &$rResource )
    {
        if ( $rResource == null ) 
        {
            $error = 'Could not initialize form with a null resource!';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
            return false;
        }

        $this->mResource =& $rResource;

        if ( $this->mResource->IsNew() ) 
        {
            $this->mWizardMode = true;
        }

        if ( isset( $_REQUEST['form'] ) )
        {
            if ( TpUtils::GetVar('form') == $this->mStep ) 
            {
                // Here user can be opening a form for the first time
                // or refreshing the same form on subsequent operations.
                if ( $_SERVER['REQUEST_METHOD'] == 'GET' )
                {
                    // Here the resource should never be new
                    return $this->LoadFromXml();
                }
                else
                {
                    // Here it can be a new or an existing resource
                    return $this->LoadFromSession();
                }
            }
            else
            {
                // In this case, resource should always be new and user is coming 
                // from a previous step, so load defaults
                return $this->LoadDefaults();
            }
        }
        else
        {
            // Here user is resuming a wizard process previously interrupted
            // TpConfigManager guessed the form. Resource should be always new here.

            // Note: LoadDefaults should usually check if there's any existing 
            // configuration, and in that case call LoadFromXml
            return $this->LoadDefaults();
        }

    } // end of member function Initialize

    function LoadDefaults( ) 
    {
        // To be used by subclasses

    } // end of member function LoadDefaults

    function LoadFromSession( ) 
    {
        // To be used by subclasses

    } // end of member function LoadFromSession

    function LoadFromXml( ) 
    {
        // To be used by subclasses

    } // end of member function LoadFromXml

    function HandleEvents( ) 
    {
        // To be used by subclasses

    } // end of member function HandleEvents

    function Done( ) 
    {
        // Property needs to be set in method handleEvents of subclasses
        return $this->mDone;

    } // end of member function Done

    function ReadyToProceed( )
    {
        return true;

    } // end of member function ReadyToProceed

    function DisplayHtml( ) 
    {
        $this->DisplayHeader();

        $this->DisplayForm();

        $this->DisplayFooter();

    } // end of member function DisplayHtml

    function DisplayForm( ) 
    {
        print("<!-- Abstract Wizard Form -->");

    } // end of member function DisplayForm

    function DisplayHeader( ) 
    {
        if ( $this->mResource == null ) 
        {
            $error_msg = 'Wizard form not properly initialized! (resource is null)';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error_msg, DIAG_ERROR );
        }

        $errors = TpDiagnostics::GetMessages();

        include('wizard_header.tmpl.php');

    } // end of member function DisplayHeader

    function DisplayFooter( ) 
    {
        include('wizard_footer.tmpl.php');

    } // end of member function DisplayFooter

    function GetStep( ) 
    {
        return $this->mStep;

    } // end of member function GetStep

    function GetNumSteps( ) 
    {
        return $this->mNumSteps;

    } // end of member function GetNumSteps

    function GetLabel( ) 
    {
        return $this->mLabel;

    } // end of member function GetLabel

    function GetResourceId( ) 
    {
        if ( $this->mResource == null )
        {
            return 'undefined';
        }

        return $this->mResource->GetCode();

    } // end of member function GetResourceId

    function InWizardMode( ) 
    {
        return $this->mWizardMode;

    } // end of member function InWizardMode

} // end of TpWizardForm
?>

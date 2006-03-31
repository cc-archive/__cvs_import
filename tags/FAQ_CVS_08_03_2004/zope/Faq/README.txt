This is a simple Faq product that shows questions and answers.
The product contains two new types, FaqFolder and FaqEntry.
FaqFolder is used for making categories of questions.

If you don't want the questions to show up in the navtree
you can disable 'showNonFolderishObject' in portal_properties/
navtree_properties.


Installation

You install it the usual way - either using QuickInstaller
product or make an External method with:

  Id: faq_install  (can really be anything)
  Module Name: Faq.Install
  Function Name: install


Requirements

  Archetypes 1.0 or later.


Remark

  Archetypes has (as of this writing - 27th oct 2003) a bug
  in 1.0.1 and possible later versions that prevents members
  to modify their own faq entries (if it's not the same member
  that owns the folder). In Referenceable.py there's a line:

  if getSecurityManager().checkPermission( mt_permission, self ):

  Change it to:

  if getSecurityManager().checkPermission( mt_permission, object ):


Todo

  What features are you missing?


Credit

  Tim Terlegard <tim@se.linux.org> -- maintainer
  Edward Muller <edwardam@interlix.com>

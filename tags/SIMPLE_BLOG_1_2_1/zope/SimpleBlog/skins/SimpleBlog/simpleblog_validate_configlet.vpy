## Script (Python) "simpleblog_validate_configlet"
##bind container=container
##bind context=context
##bind namespace=
##bind script=script
##bind state=state
##bind subpath=traverse_subpath
##parameters=maxItemsInPortlet
##title=Validates the SimpleBlog configlet
##

if not maxItemsInPortlet:
    state.setError('maxItemsInPortlet', 'Please enter a value')
else:
    try:
        i=int(maxItemsInPortlet)
        if i<1:
            state.setError('maxItemsInPortlet', 'Please enter an integer larger than 0')
    except ValueError:
       state.setError('maxItemsInPortlet', 'Please enter an integer')

if state.getErrors():
    return state.set(status='failure', portal_status_message='Please correct the indicated errors.')
else:
    return state.set(status='success', portal_status_message='ok')
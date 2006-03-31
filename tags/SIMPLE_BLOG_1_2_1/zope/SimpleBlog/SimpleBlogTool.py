from Products.CMFCore.utils import UniqueObject 
from OFS.SimpleItem import SimpleItem
from OFS.PropertyManager import PropertyManager
from Globals import InitializeClass
from AccessControl import ClassSecurityInfo, Unauthorized
from Products.CMFCore import CMFCorePermissions
import zLOG,os

import calendar
calendar.setfirstweekday(6) #start day  Mon(0)-Sun(6)
from DateTime import DateTime


class SimpleBlogManager(UniqueObject, SimpleItem,PropertyManager): 
    """ This tool provides some functions for SimpleBlog objects """ 
    id = 'simpleblog_tool' 
    meta_type= 'SimpleBlog manager' 
    plone_tool = 1
        
    manage_options=PropertyManager.manage_options
    
    security = ClassSecurityInfo()
    calendar_types=['BlogEntry']
    use_session=""
    
    def __init__(self):
        self.manage_addProperty('publishedState', 'published', 'string')
        self.manage_addProperty('maxItemsInPortlet', 5, 'int')
        self.manage_addProperty('globalCategories', '', 'lines')
        self.manage_addProperty('showStandardButtons', 1,'boolean')
        self.manage_addProperty('createPortletOnBlogCreation', 1,'boolean')
        self.manage_addProperty('showIcons', 1,'boolean')


    def _getState(self):
        try:
            return self.publishedState
        except:
            return 'published'

    def _getMaxItemsInPortlet(self):
        try:
            return self.maxItemsInPortlet
        except:
            return 5
    def _getGlobalCategories(self):
        try:
            cats = self.globalCategories
            ret=[]
            for c in cats:
                if c!='':
                    ret.append(c)
            return ret
        except:
            return []
       
    def _getShowStandardButtons(self):
        try:
            return self.showStandardButtons
        except:
            return 1

    def _getShowIcons(self):
        if not self.hasProperty('showIcons'):
            self.manage_addProperty('showIcons', 1,'boolean')
        try:
            return self.showIcons
        except:
            return 1
        
    def _getCreatePortletOnBlogCreation(self):
        try:
            return self.createPortletOnBlogCreation
        except:
            return 1

    security.declareProtected(CMFCorePermissions.ManagePortal,'setProperties')        
    def setProperties(self, publishedState='published', createPortletOnBlogCreation=None, maxItemsInPortlet=5, globalCategories='', showStandardButtons=1, showIcons=None):
        self.publishedState = publishedState
        if createPortletOnBlogCreation==1 or createPortletOnBlogCreation=='on':
            self.createPortletOnBlogCreation=1
        else:
            self.createPortletOnBlogCreation=0

        if showIcons==1 or showIcons=='on':
            self.showIcons = 1
        else:
            self.showIcons = 0
            
        self.maxItemsInPortlet=int(maxItemsInPortlet)

        value=''
        if globalCategories<>'':
            value =  globalCategories.split('\n')
            value = [v.strip() for v in value if v.strip()]
            value = filter(None, value)

        self.globalCategories=value
        self.showStandardButtons=int(showStandardButtons)
    
    security.declarePublic('getPublishedState')
    def getPublishedState(self):
        return self._getState()
    
    security.declarePublic('getMaxItemsInPortlet')
    def getMaxItemsInPortlet(self):
        return self._getMaxItemsInPortlet()
    
    security.declarePublic('getFrontPage')
    def getFrontPage(self, context):
        """
        returns the frontpage (Blog object) when viewing an Entry
        """
        if context.portal_type!='Blog':
            portal = context.portal_url.getPortalObject()
            if context!=portal:
                parent=context.aq_parent
            else:
                parent=context
            found=0
            while parent!=portal and context.portal_type!='Blog':
                if parent.portal_type=='Blog':
                    found=1
                    break
                parent=parent.aq_parent
            
            if found==1:
                return parent
            else:
                return None
        else:
            return context
     
    security.declarePublic('getAvailableCategories')
    def getAvailableCategories(self, context):
        """
        returns a dict of all the available categories with the number of posts inside
        """
        # get all EntryFolders
        # first get the starting point in case we are inside a Blog section
        # if we are higher in the tree than any Blog then we will end up in the portalobject itself
        # in that case we just search for categories starting in context.

        startpoint = self.getStartpoint(context, fromHere=0)

        # now we have the starting point for our search
        
        result = startpoint.portal_catalog.searchResults(meta_type=['BlogFolder', 'Blog'], path={'query':self.getObjectPath(startpoint),'level':0})
        
        # now fetch all the available categories
        categories=[]
        for o in result:
            obj=o.getObject()
            cats = obj.getCategories()
            for c in cats:
                if not c in categories:
                    categories.append(c)
        
        # add the global categories
        for c in self.getGlobalCategories():
            if not c in categories:
                categories.append(c)
        
        # now we have a list of unique categories available from startpoint and deeper in tree
        # next step is to count the number of entries for each category
        rescats={}
        for c in categories:
            result = startpoint.portal_catalog.searchResults(review_state=self._getState(), meta_type='BlogEntry', EntryCategory=c,  path={'query':self.getObjectPath(startpoint),'level':0})
            rescats[c]=len(result)
        return rescats        
    
    security.declarePublic('getSortedKeys')
    def getSortedKeys(self, dict):
        keys = dict.keys()
        keys.sort()
        return keys
    
    security.declarePublic('getGlobalCategories')
    def getGlobalCategories(self):
        return self._getGlobalCategories()
    
    security.declarePublic('searchForEntries')
    def getStartpoint(self, context, fromHere=0):
        if context.portal_type!='Blog' and fromHere==0:
            portal = context.portal_url.getPortalObject()
            if context!=portal:
                parent=context.aq_parent
            else:
                parent=context
            found=0
            while parent!=portal and context.portal_type!='Blog':
                if parent.portal_type=='Blog':
                    found=1
                    break
                parent=parent.aq_parent
            
            if found==1:
                startpoint=parent
            else:
                if context.isPrincipiaFolderish:
                    startpoint=context
                else:
                    startpoint=context.aq_parent
        else:
            startpoint=context

        return startpoint
    
    security.declarePublic('searchForEntries')
    def searchForEntries(self, context, category=None, maxResults=None, fromHere=0):
        # set maxResults=0 for all the results,
        # leave it to None to get the max from the properties
        # set fromHere=1 to search from the current location. Is used for BlogFolders
        
        # first, get the context right
        # when inside a Blog: search for the frontpage
        # when outside a Blog: use context (or its container)
            
        startpoint = self.getStartpoint(context, fromHere)
        # now we have the starting point for our search
        
        query={}
        
        if category!=None:
            query['EntryCategory']=category
            
        query['getAlwaysOnTop']=1
        resultsTop = startpoint.portal_catalog.searchResults(query, review_state=self._getState(), meta_type='BlogEntry', path={'query':self.getObjectPath(startpoint),'level':0}, sort_order='reverse', sort_on='effective')
        
        query['getAlwaysOnTop']=0
        resultsNoTop = startpoint.portal_catalog.searchResults(query, review_state=self._getState(), meta_type='BlogEntry', path={'query':self.getObjectPath(startpoint),'level':0}, sort_order='reverse', sort_on='effective')
        
        results = resultsTop + resultsNoTop

        
        if maxResults==0:
            return results
        elif maxResults==None:
            return results[:self._getMaxItemsInPortlet()]
        else:
            return results[:maxResults]    

    security.declarePublic('searchForDay')
    def searchForDay(self, context, date):
        startpoint = self.getStartpoint(context, fromHere=0)
        # now we have the starting point for our search
        
        query={'start': DateTime(date).earliestTime(), 'start_usage': 'range:min', 
                    'end': DateTime(date).latestTime(), 'end_usage':'range:max'}
        query['getAlwaysOnTop']=1
        resultsTop = startpoint.portal_catalog.searchResults(query, 
                                                             review_state=self._getState(), 
                                                             meta_type='BlogEntry', 
                                                             path={'query':self.getObjectPath(startpoint),'level':0}, 
                                                             sort_order='reverse', sort_on='effective')
        
        
        query['getAlwaysOnTop']=0
        resultsNoTop = startpoint.portal_catalog.searchResults(query, 
                                                             review_state=self._getState(), 
                                                             meta_type='BlogEntry', 
                                                             path={'query':self.getObjectPath(startpoint),'level':0}, 
                                                             sort_order='reverse', sort_on='effective')
        
        results = resultsTop + resultsNoTop
        return results
   
    security.declarePublic('blogHasEntries')
    def blogHasEntries(self, context, fromHere=0):
        """
        returns if a blog has entries, either published or not published. 
        this function is used to display a message in the simpleblog(folder)_view when
        there are entries but none of them published
        """
        startpoint = self.getStartpoint(context, fromHere=0)
        
        # get all entries, doesn't matter what state they're in
        results = startpoint.portal_catalog.searchResults(meta_type='BlogEntry', path={'query':self.getObjectPath(startpoint),'level':0})        
        
        if results:
            return True
        else:
            return False
    
    security.declarePublic('getEntryDate')
    def getEntryDate(self, context):
        if context.EffectiveDate()=='None':
            return context.modification_date.aCommon()
        else:
            return context.EffectiveDate()

    security.declarePublic('getShowStandardButtons')
    def getShowStandardButtons(self):
        return self._getShowStandardButtons()

    security.declarePublic('getShowIcons')
    def getShowIcons(self):
        return self._getShowIcons()
    
    security.declarePublic('getCreatePortletOnBlogCreation')
    def getCreatePortletOnBlogCreation(self):
        return self._getCreatePortletOnBlogCreation()
        
    security.declareProtected(CMFCorePermissions.ManagePortal,'getAllWorkflowStates')
    def getAllWorkflowStates(self, context):
        lst=[]
        for wf in context.portal_workflow.listWorkflows():
            states = context.portal_workflow.getWorkflowById(wf).states
            for s in states.keys():
                if not states[s].id in lst:
                    lst.append(states[s].id)
        return lst
    
    security.declareProtected(CMFCorePermissions.ManagePortal,'getEntryWorkflowStates')
    def getEntryWorkflowStates(self, context):
        chain = context.portal_workflow.getChainForPortalType('BlogEntry', 0)
        lst=[]
        for wf in chain:
            states = context.portal_workflow.getWorkflowById(wf).states
            for s in states.keys():
                if not states[s].id in lst:
                    lst.append(states[s].id)
        
        return lst

    # return object's url relative to the portal
    def getObjectPath(self, object):
        return os.path.join(*object.getPhysicalPath()).replace('\\', '/')
        
    # ======================================================
    # calendar stuff, copied from CMFCalender
    # ======================================================

    security.declarePublic('getCalendarTypes')
    def getCalendarTypes(self):
        """ Returns a list of type that will show in the calendar """
        return self.calendar_types

    security.declarePublic('getUseSession')
    def getUseSession(self):
        """ Returns the Use_Session option """
        return self.use_session

    security.declarePublic('getDays')
    def getDays(self):
        """ Returns a list of days with the correct start day first """        
        return calendar.weekheader(2).split()
        
    security.declarePublic('getWeeksList')
    def getWeeksList(self, month='1', year='2002'):
        """Creates a series of weeks, each of which contains an integer day number.
           A day number of 0 means that day is in the previous or next month.
        """
        # daysByWeek is a list of days inside a list of weeks, like so:
        # [[0, 1, 2, 3, 4, 5, 6],
        #  [7, 8, 9, 10, 11, 12, 13],
        #  [14, 15, 16, 17, 18, 19, 20],
        #  [21, 22, 23, 24, 25, 26, 27],
        #  [28, 29, 30, 31, 0, 0, 0]]
        daysByWeek=calendar.monthcalendar(year, month)
    
        return daysByWeek

    security.declarePublic('getEventsForCalendar')
    def getEventsForCalendar(self, context, month='1', year='2002'):
        """ recreates a sequence of weeks, by days each day is a mapping.
            {'day': #, 'url': None}
        """
        year=int(year)
        month=int(month)
        # daysByWeek is a list of days inside a list of weeks, like so:
        # [[0, 1, 2, 3, 4, 5, 6],
        #  [7, 8, 9, 10, 11, 12, 13],
        #  [14, 15, 16, 17, 18, 19, 20],
        #  [21, 22, 23, 24, 25, 26, 27],
        #  [28, 29, 30, 31, 0, 0, 0]]
        daysByWeek=calendar.monthcalendar(year, month)
        weeks=[]
        
        events=self.catalog_getevents(context, year, month)
    
        for week in daysByWeek:
            days=[]
            for day in week:
                if events.has_key(day):
                    days.append(events[day])
                else:
                    days.append({'day': day, 'event': 0, 'eventslist':[]})
                
            weeks.append(days)
            
        return weeks
    
    security.declarePublic('catalog_getevents')
    def catalog_getevents(self, context, year, month):
        """ given a year and month return a list of days that have events """
        first_date=DateTime(str(month)+'/1/'+str(year))
        last_day=calendar.monthrange(year, month)[1]
        ## This line was cropping the last day of the month out of the
        ## calendar when doing the query
        ## last_date=DateTime(str(month)+'/'+str(last_day)+'/'+str(year))
        last_date=first_date + last_day    
        
        # get the starting point for our search. This is where we depart from the standard catalog_tool:
        startpoint = self.getStartpoint(context, fromHere=0)
        
        query=self.portal_catalog(portal_type=self.calendar_types,
                              review_state=self._getState(),
                              start=last_date,
                              start_usage='range:max',
                              end=first_date,
                              end_usage='range:min',
                              path={'query':self.getObjectPath(startpoint),'level':0},
                              sort_on='start')
        
        # compile a list of the days that have events
        eventDays={}
        for daynumber in range(1, 32): # 1 to 31
            eventDays[daynumber] = {'eventslist':[], 'event':0, 'day':daynumber}
        includedevents = []
        for result in query:
            if result.getRID() in includedevents:
                break
            else:
                includedevents.append(result.getRID())
            event={}
            # we need to deal with events that end next month
            if  result.end.month() != month:  # doesn't work for events that last ~12 months - fix it if it's a problem, otherwise ignore
                eventEndDay = last_day
                event['end'] = None
            else:
                eventEndDay = result.end.day()
                event['end'] = result.end.Time()
            # and events that started last month
            if result.start.month() != month:  # same as above re: 12 month thing
                eventStartDay = 1
                event['start'] = None
            else:
                eventStartDay = result.start.day()
                event['start'] = result.start.Time()
            event['title'] = result.Title or result.id
            if eventStartDay != eventEndDay:
                allEventDays = range(eventStartDay, eventEndDay+1)
                eventDays[eventStartDay]['eventslist'].append({'end':None, 'start':result.start.Time(), 'title':result.Title})
                eventDays[eventStartDay]['event'] = 1
                for eventday in allEventDays[1:-1]:
                    eventDays[eventday]['eventslist'].append({'end':None, 'start':None, 'title':result.Title})
                    eventDays[eventday]['event'] = 1
                eventDays[eventEndDay]['eventslist'].append({'end':result.end.Time(), 'start':None, 'title':result.Title})
                eventDays[eventEndDay]['event'] = 1
            else:
                eventDays[eventStartDay]['eventslist'].append(event)
                eventDays[eventStartDay]['event'] = 1
            # This list is not uniqued and isn't sorted
            # uniquing and sorting only wastes time
            # and in this example we don't need to because
            # later we are going to do an 'if 2 in eventDays'
            # so the order is not important.
            # example:  [23, 28, 29, 30, 31, 23]
        return eventDays

        # ==================
        # end calendar stuff
        # ==================
    
    
    
InitializeClass(SimpleBlogManager)
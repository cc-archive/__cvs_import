import shelve
import os

import zope.interface

import interfaces
import events

import storage.interface
import storage.events

import zope.component
from support import deinstify

class Publisher:
    zope.interface.implements(interfaces.IPublisher);
    
    def __init__(self, settings, storage_providers=()):
        self.settings = settings
        
        self.items = []
        self.storage = [n(self) for n in storage_providers]

        # read preferences, if any
        self.__readPrefs()
        
        # register event handlers
        zope.component.provideHandler(
            zope.component.adapter(storage.events.IItemSelected)(
                deinstify(self.selectItem))
            )
        zope.component.provideHandler(
            zope.component.adapter(storage.events.IItemDeselected)(
                deinstify(self.deselectItem))
            )
        
    def close(self):
        # write out preferences
        self.__writePrefs()
        
    def __readPrefs(self):
        self.prefs = shelve.open(self.settings.PREFS_FILE)
       
    def __writePrefs(self):
        self.prefs.close()
        
    #----------------------------------------------------------------        
    # File Handling methods
    
    @zope.component.adapter(storage.events.IItemDeselected)
    @deinstify
    def deselectItem(self, event):
        
        items = [n for n in self.items if n.split(os.sep)[-1] == event.item_id]

        for item in items:
            del self.items[self.items.index(item)]
            
            zope.component.handle(events.UpdateItemList(item, removed=True))

    @zope.component.adapter(storage.events.IItemSelected)
    @deinstify
    def selectItem(self, event):
        """Respond to item selection events."""
        filename = event.item_id
        
        if filename not in self.items:
            self.items.append(filename)

            zope.component.handle(events.UpdateItemList(filename))
        else:
            return
        
        
    #----------------------------------------------------------------        
    # Metadata Convenience methods
    def getField(self, key):
        """Return the first metadata field with the given key from any
        storage provider."""
        for s in self.storage:
            for section in s.getSections():
                try:
                    return section.getField(key)
                except KeyError:
                    pass
                    
        raise KeyError
                    
    #----------------------------------------------------------------        
    
    def setLicense(self, license_url, license_name):
        self.license_url = license_url
        self.license_name = license_name
        
    def setVUrl(self, vurl):
        self.verificationUrl = vurl
        
    def setCopyrightHolder(self, holder, year):
        self.copyrightHolder = holder
        self.copyrightYear = year
        
    def setUserInterface(self, ui):
        self.__ui = ui
            
    def embed (self, event=None):
        # get form values
        license = self.getField('licenseurl').getValue()
        verify_url = self.verificationUrl
        year = self.getField('copyrightyear').getValue()
        holder = self.getField('copyrightholder').getValue()

        for filename in self.files:
            try:
                 metadata(filename).embed(license, verify_url, year, holder)
            except NotImplementedError, e:
                 pass

        # generate the verification RDF
        self.verificationRdf = rdf.generate(self.files, verify_url, 
                                    license, year, holder,
                                    work_meta=self.allMeta())
                                    
        #print self.verificationRdf
       
    def store(self):
        """Embed the license and upload files to archive.org"""
        for store in self.storage:
            store.clearFiles()
            
            for f in self.files:
                # TODO: Only collect metadata that applies to a file
                store.addFile(f, self.allMeta())
                
            store.store()
            
    def allMeta(self):
        """Returns a dictionary containing all metadata values provided
        by the associated storage(s)"""
        metadict = {}
        
        for store in self.storage:
            for section in store.getSections():
                for field in section.getFields():
                    metadict[field.key] = field.getValue()
                    
        return metadict
        
    def claimString(self, license, verification, year, holder):
        return "%s %s. Licensed to the public under %s verify at %s" % (
            year, holder, license, verification )
import zope.interface


class IPublisher(zope.interface.Interface):
    
    def close():
        """Clean up the publisher before shutting down."""
        
    def store():
        """Store the selected items to the backend."""
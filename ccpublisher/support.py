def deinstify(func):
    def foo(*args, **kwargs):
        func(*args, **kwargs)
        
    return foo
    

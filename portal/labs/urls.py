from django.urls import path

from . import views

app_name = 'labs'

urlpatterns = [
    path('', views.LabListView.as_view(), name='lab_list'),
    path('<slug:slug>/start/', views.start_lab, name='start'),
    # No slug: a student has at most one instance running.
    path('instance/extend/', views.extend_lab, name='extend'),
    path('instance/stop/', views.stop_lab, name='stop'),
]
